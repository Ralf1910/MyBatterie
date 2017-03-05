<?
//  Modul zur Steuerung des Vorwerk Kobold VR200
//
//	Version 0.9
//
// ************************************************************

class Batterie extends IPSModule {


	public function Create() {
		// Diese Zeile nicht l�schen.
		parent::Create();

		$archiv = IPS_GetInstanceIDByName("Archiv", 0 );

		// Verbraucher, Erzeuger und Batteriedaten konfigurieren
		$this->RegisterPropertyInteger("Archiv",$archiv);
		$this->RegisterPropertyInteger("Verbraucher1", 0);
		$this->RegisterPropertyInteger("Verbraucher2", 0);
		$this->RegisterPropertyInteger("Verbraucher3", 0);
		$this->RegisterPropertyInteger("Verbraucher4", 0);
		$this->RegisterPropertyInteger("Verbraucher5", 0);
		$this->RegisterPropertyInteger("Erzeuger1", 0);
		$this->RegisterPropertyInteger("Erzeuger2", 0);
		$this->RegisterPropertyInteger("Erzeuger3", 0);
		$this->RegisterPropertyInteger("Erzeuger4", 0);
		$this->RegisterPropertyInteger("Erzeuger5", 0);
		$this->RegisterPropertyInteger("Kapazitaet", 7000);
		$this->RegisterPropertyInteger("MaxLadeleistung", 2000);




		// Variablen anlegen
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("fuellstand", "Batterie - F�llstand", "~Electricity", 10), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("fuellstandProzent", "Batterie - F�llstand Prozent", "Integer.Prozent", 20), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("zyklen", "Batterie - Zyklen", "", 30), true);

		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuelleLadeleistung", "Power - Ladeleistung", "Power.Watt", 110), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuelleEinspeisung", "Power - Einspeisung", "Power.Watt", 120), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuelleEigennutzung", "Power - Eigennutzung", "Power.Watt", 130), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuellerNetzbezug", "Power - Netzbezug", "Power.Watt", 140), true);

		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("eingespeisteEnergie", "Energie - eingespeist", "~Electricity", 210), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("eingespeisteEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("selbstverbrauchteEnergie", "Energie - selbstverbraucht", "~Electricity", 220), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("selbstverbrauchteEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("bezogeneEnergie", "Energie - bezogen", "~Electricity", 230), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("bezogeneEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("gespeicherteEnergie", "Energie - gespeichert", "~Electricity", 240), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("gespeicherteEnergie"), 1);


		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("EVGV", "Eigenverbrauch / Gesamtverbrauch", "Float.Prozent", 310), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("EVGP", "Eigenverbrauch / Gesamtproduktion", "Float.Prozent", 320), true);

		AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("rollierendeZyklen", "Pro Jahr - Zyklen", "", 410), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeEingespeisteEnergie", "Pro Jahr - Eingespeiste Energie", "~Electricity", 420), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeEingespeisteEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeSelbstvertrauchteEnergie", "Pro Jahr - Selbstverbrauchte Energie", "~Electricity", 430), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeSelbstvertrauchteEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeBezogeneEnergie", "Pro Jahr - Bezogene Energie", "~Electricity", 440), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeBezogeneEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeGespeicherteEnergie", "Pro Jahr - Gespeicherte Energie", "~Electricity", 450), true);
		AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeGespeicherteEnergie"), 1);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeEVGV", "Pro Jahr - Eigenverbrauch / Gesamtverbrauch", "Float.Prozent", 460), true);
		AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeEVGP", "Pro Jahr - Eigenverbrauch / Gesamtproduktion", "Float.Prozent", 470), true);



		// Updates einstellen
		$this->RegisterTimer("Update", 60*1000, 'BAT_Update($_IPS[\'TARGET\']);');

	}


	// �berschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {
		// Diese Zeile nicht l�schen
		parent::ApplyChanges();

		//Timerzeit setzen in Minuten
		$this->SetTimerInterval("Update", 60*1000);
	}

	// Berechnung der jeweiligen Jahreswerte
	private function RollierenderJahreswert(Integer $VariableID) {
		//Den Datensatz von vor 365,25 Tagen abfragen (zur Ber�cksichtigung von Schaltjahren)
		$historischeWerte = AC_GetLoggedValues($this->ReadPropertyInteger("Archiv"), $VariableID , time()-1000*24*60*60, time()-365.25*24*60*60, 1);
		$wertVor365d = 0;
		foreach($historischeWerte as $wertVorEinemJahr) {
			$wertVor365d = $wertVorEinemJahr['Value'];
		}

		return (GetValue($VariableID) - $wertVor365d);
	}


	// Aktualisiert die Batteriedaten
	public function Update() {

		// Gesamtverbrauch zusammenaddieren
		$aktuellerVerbrauch 	= 	0;
		if ($this->ReadPropertyInteger("Verbraucher1")>0) $aktuellerVerbrauch += getValue($this->ReadPropertyInteger("Verbraucher1"));
		if ($this->ReadPropertyInteger("Verbraucher2")>0) $aktuellerVerbrauch += getValue($this->ReadPropertyInteger("Verbraucher2"));
		if ($this->ReadPropertyInteger("Verbraucher3")>0) $aktuellerVerbrauch += getValue($this->ReadPropertyInteger("Verbraucher3"));
		if ($this->ReadPropertyInteger("Verbraucher4")>0) $aktuellerVerbrauch += getValue($this->ReadPropertyInteger("Verbraucher4"));
		if ($this->ReadPropertyInteger("Verbraucher5")>0) $aktuellerVerbrauch += getValue($this->ReadPropertyInteger("Verbraucher5"));

		// Gesamterzeugung zusammenaddieren
		$aktuelleErzeugung		=	0;
		if ($this->ReadPropertyInteger("Erzeuger1")>0) $aktuelleErzeugung += getValue($this->ReadPropertyInteger("Erzeuger1"));
		if ($this->ReadPropertyInteger("Erzeuger2")>0) $aktuelleErzeugung += getValue($this->ReadPropertyInteger("Erzeuger2"));
		if ($this->ReadPropertyInteger("Erzeuger3")>0) $aktuelleErzeugung += getValue($this->ReadPropertyInteger("Erzeuger3"));
		if ($this->ReadPropertyInteger("Erzeuger4")>0) $aktuelleErzeugung += getValue($this->ReadPropertyInteger("Erzeuger4"));
		if ($this->ReadPropertyInteger("Erzeuger5")>0) $aktuelleErzeugung += getValue($this->ReadPropertyInteger("Erzeuger5"));

		$bezogeneEnergie			= 	getValue($this->GetIDforIdent("bezogeneEnergie"));

		$eingespeisteEnergie		=	getValue($this->GetIDforIdent("eingespeisteEnergie"));

		$gespeicherteEnergie		=	getValue($this->GetIDforIdent("gespeicherteEnergie"));

		$selbstvertrauchteEnergie	= 	getValue($this->GetIDforIdent("selbstverbrauchteEnergie"));

		$maxLadeleistung			= 	$this->ReadPropertyInteger("MaxLadeleistung");

		$kapazitaet					=	$this->ReadPropertyInteger("Kapazitaet")/1000;

		$fuellstand					=	getValue($this->GetIDforIdent("fuellstand"));





		// Berechnung, der einzelnen Werte
		if ($aktuellerVerbrauch > $aktuelleErzeugung) {
			if ($fuellstand <= 0) {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), max($aktuellerVerbrauch - $aktuelleErzeugung,0));
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), 0);
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), 0);
				setValue($this->GetIDforIdent("bezogeneEnergie"), $bezogeneEnergie + max($aktuellerVerbrauch - $aktuelleErzeugung,0)/60000);
				setValue($this->GetIDforIdent("fuellstand"), 0);
			} else {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), max($aktuellerVerbrauch - $aktuelleErzeugung - $maxLadeleistung,0));
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), max($aktuelleErzeugung - $aktuellerVerbrauch, -1*$maxLadeleistung));
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), 0);
				setValue($this->GetIDforIdent("bezogeneEnergie"), $bezogeneEnergie + max($aktuellerVerbrauch - $aktuelleErzeugung - $maxLadeleistung,0)/60000);
				setValue($this->GetIDforIdent("fuellstand"), max($fuellstand + max($aktuelleErzeugung - $aktuellerVerbrauch, -1*$maxLadeleistung)/60000, 0));
			}
		} else {
			if ($fuellstand >= $kapazitaet) {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), 0);
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), 0);
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), max($aktuelleErzeugung - $aktuellerVerbrauch,0));
				setValue($this->GetIDforIdent("eingespeisteEnergie"), $eingespeisteEnergie + max($aktuelleErzeugung - $aktuellerVerbrauch,0)/1000/60);
				setValue($this->GetIDforIdent("fuellstand"), $kapazitaet);
			} else {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), 0);
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), min($aktuelleErzeugung - $aktuellerVerbrauch, $maxLadeleistung));
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), max($aktuelleErzeugung - $aktuellerVerbrauch - $maxLadeleistung,0));
				setValue($this->GetIDforIdent("eingespeisteEnergie"), $eingespeisteEnergie + max($aktuelleErzeugung - $aktuellerVerbrauch - $maxLadeleistung,0)/60000);
				setValue($this->GetIDforIdent("fuellstand"), min($fuellstand + min($aktuelleErzeugung - $aktuellerVerbrauch, $maxLadeleistung)/60000, $kapazitaet));
				setValue($this->GetIDforIdent("gespeicherteEnergie"), $gespeicherteEnergie + min($aktuelleErzeugung - $aktuellerVerbrauch, $maxLadeleistung)/60000);
			}
		}

		SetValue($this->GetIDforIdent("zyklen"), getValue($this->GetIDforIdent("gespeicherteEnergie")) / $kapazitaet);

		SetValue($this->GetIDforIdent("fuellstandProzent"), round((getValue($this->GetIDforIdent("fuellstand"))*100 / $kapazitaet)/5)*5);

		SetValue($this->GetIDforIdent("rollierendeEingespeisteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("eingespeisteEnergie")));
		SetValue($this->GetIDforIdent("rollierendeSelbstvertrauchteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("selbstverbrauchteEnergie")));
		SetValue($this->GetIDforIdent("rollierendeBezogeneEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("bezogeneEnergie")));
		SetValue($this->GetIDforIdent("rollierendeGespeicherteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("gespeicherteEnergie")));
		SetValue($this->GetIDforIdent("rollierendeEVGV"), $this->RollierenderJahreswert($this->GetIDforIdent("EVGV")));
		SetValue($this->GetIDforIdent("rollierendeEVGP"), $this->RollierenderJahreswert($this->GetIDforIdent("EVGP")));

		SetValue($this->GetIDforIdent("rollierendeZyklen"), $this->RollierenderJahreswert($this->GetIDforIdent("zyklen")));

		SetValue($this->GetIDforIdent("aktuelleEigennutzung"), min($aktuellerVerbrauch, $aktuelleErzeugung));

		SetValue($this->GetIDforIdent("selbstverbrauchteEnergie"), $selbstvertrauchteEnergie + min($aktuellerVerbrauch, $aktuelleErzeugung)/60000);

		SetValue($this->GetIDforIdent("EVGV"), ($selbstvertrauchteEnergie + $gespeicherteEnergie)*100 / ($bezogeneEnergie + $selbstvertrauchteEnergie + $gespeicherteEnergie));

		SetValue($this->GetIDforIdent("EVGP"), ($selbstvertrauchteEnergie + $gespeicherteEnergie)*100 / ($eingespeisteEnergie + $selbstvertrauchteEnergie + $gespeicherteEnergie));

	}


 }

