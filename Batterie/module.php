<?
//  Modul zur Simulation von Batterien mit verschiedenen
//  Verbrauchern und Erzeugern
//	Version 0.9
//
// ************************************************************

class Batterie extends IPSModule { 


	public function Create() {
		// Diese Zeile nicht löschen.
		parent::Create();

		$archiv = IPS_GetInstanceIDByName("Archiv", 0 );

		// Verbraucher, Erzeuger und Batteriedaten konfigurieren
		$this->RegisterPropertyInteger("Archiv",$archiv);
		$this->RegisterPropertyInteger("VerbraucherP1", 24149 /*[Energie\Haushalt\aktuelle Leistung HH]*/);
		$this->RegisterPropertyInteger("VerbraucherW1", 26067 /*[Energie\Haushalt\Verbrauch letzte Minute HH]*/);
		$this->RegisterPropertyInteger("VerbraucherP2", 16212 /*[Energie\Wärmepumpe\aktuelle Leistung WP]*/);
		$this->RegisterPropertyInteger("VerbraucherW2", 32308 /*[Energie\Wärmepumpe\Verbrauch letzte Minute WP]*/);
		$this->RegisterPropertyInteger("VerbraucherP3", 0);
		$this->RegisterPropertyInteger("VerbraucherW3", 0);
		$this->RegisterPropertyInteger("ErzeugerP1", 22545 /*[Energie\PV-Anlage Süd\aktuelle Leistung PS]*/);
		$this->RegisterPropertyInteger("ErzeugerW1", 44022 /*[Energie\PV-Anlage Süd\Verbrauch letzte Minute PS]*/);
		$this->RegisterPropertyInteger("ErzeugerP2", 16594 /*[Energie\PV-Anlage Nord\aktuelle Leistung PN]*/);
		$this->RegisterPropertyInteger("ErzeugerW2", 45660 /*[Energie\PV-Anlage Nord\Verbrauch letzte Minute PN]*/);
		$this->RegisterPropertyInteger("ErzeugerP3", 0);
		$this->RegisterPropertyInteger("ErzeugerW3", 0);
		$this->RegisterPropertyInteger("Kapazitaet", 13500);
		$this->RegisterPropertyInteger("MaxLadeleistung", 7000);



		// Updates einstellen
		$this->RegisterTimer("Update", 60*1000, 'BAT_Update($_IPS[\'TARGET\']);');

	}


	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {
		// Diese Zeile nicht löschen
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel ( ) == 10103) {

			$archiv = IPS_GetInstanceIDByName("Archiv", 0 );

			// Variablen anlegen und auch gleich dafür sorgen, dass sie geloggt werden
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("fuellstand", "Batterie - Füllstand", "~Electricity", 10), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("fuellstandProzent", "Batterie - Füllstand Prozent", "Integer.Prozent", 20), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("zyklen", "Batterie - Zyklen", "Float.BatterieZyklen", 30), true);

			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuelleLadeleistung", "Power - Ladeleistung", "Integer.Watt", 110), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuelleEinspeisung", "Power - Einspeisung", "Integer.Watt", 120), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuelleEigennutzung", "Power - Eigennutzung", "Integer.Watt", 130), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("aktuellerNetzbezug", "Power - Netzbezug", "Integer.Watt", 140), true);

			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("eingespeisteEnergie", "Energie - eingespeist", "~Electricity", 210), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("eingespeisteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("selbstverbrauchteEnergie", "Energie - direktverbraucht", "~Electricity", 220), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("selbstverbrauchteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("bezogeneEnergie", "Energie - bezogen", "~Electricity", 230), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("bezogeneEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("gespeicherteEnergie", "Energie - gespeichert", "~Electricity", 240), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("gespeicherteEnergie"), 1);


			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("EVGV", "Eigenverbrauch / Gesamtverbrauch", "Float.Prozent", 310), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("EVGP", "Eigenverbrauch / Gesamtproduktion", "Float.Prozent", 320), true);

			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeZyklen", "Pro Jahr - Zyklen", "Float.BatterieZyklen", 410), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeEingespeisteEnergie", "Pro Jahr - Eingespeiste Energie", "~Electricity", 420), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeEingespeisteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeSelbstverbrauchteEnergie", "Pro Jahr - Direktverbrauchte Energie", "~Electricity", 430), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeSelbstverbrauchteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeBezogeneEnergie", "Pro Jahr - Bezogene Energie", "~Electricity", 440), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeBezogeneEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeGespeicherteEnergie", "Pro Jahr - Gespeicherte Energie", "~Electricity", 450), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("rollierendeGespeicherteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeEVGV", "Pro Jahr - Eigenverbrauch / Gesamtverbrauch", "Float.Prozent", 460), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("rollierendeEVGP", "Pro Jahr - Eigenverbrauch / Gesamtproduktion", "Float.Prozent", 470), true);

		}

		//Timerzeit setzen in Minuten
		$this->SetTimerInterval("Update", 60*1000);
	}

	// Berechnung der jeweiligen Jahreswerte
	private function RollierenderJahreswert($VariableID) {
		//Den Datensatz von vor 365 Tagen abfragen (zur Berücksichtigung von Schaltjahren)
		$historischeWerte = AC_GetLoggedValues($this->ReadPropertyInteger("Archiv"), $VariableID , time()-1000*24*60*60, time()-365*24*60*60, 1);
		$wertVor365d = 0;
		foreach($historischeWerte as $wertVorEinemJahr) {
			$wertVor365d = $wertVorEinemJahr['Value'];
		}

		return (GetValue($VariableID) - $wertVor365d);
	}


	// Aktualisiert die Batteriedaten
	public function Update() {

		// Gesamtverbrauch zusammenaddieren
		$aktuellerVerbrauchP 	= 	0;
		if ($this->ReadPropertyInteger("VerbraucherP1")>0) $aktuellerVerbrauchP += getValue($this->ReadPropertyInteger("VerbraucherP1"));
		if ($this->ReadPropertyInteger("VerbraucherP2")>0) $aktuellerVerbrauchP += getValue($this->ReadPropertyInteger("VerbraucherP2"));
		if ($this->ReadPropertyInteger("VerbraucherP3")>0) $aktuellerVerbrauchP += getValue($this->ReadPropertyInteger("VerbraucherP3"));

		$aktuellerVerbrauchW 	= 	0;
		if ($this->ReadPropertyInteger("VerbraucherW1")>0) $aktuellerVerbrauchW += getValue($this->ReadPropertyInteger("VerbraucherW1"));
		if ($this->ReadPropertyInteger("VerbraucherW2")>0) $aktuellerVerbrauchW += getValue($this->ReadPropertyInteger("VerbraucherW2"));
		if ($this->ReadPropertyInteger("VerbraucherW3")>0) $aktuellerVerbrauchW += getValue($this->ReadPropertyInteger("VerbraucherW3"));


		// Gesamterzeugung zusammenaddieren
		$aktuelleErzeugungP		=	0;
		if ($this->ReadPropertyInteger("ErzeugerP1")>0) $aktuelleErzeugungP += getValue($this->ReadPropertyInteger("ErzeugerP1"));
		if ($this->ReadPropertyInteger("ErzeugerP2")>0) $aktuelleErzeugungP += getValue($this->ReadPropertyInteger("ErzeugerP2"));
		if ($this->ReadPropertyInteger("ErzeugerP3")>0) $aktuelleErzeugungP += getValue($this->ReadPropertyInteger("ErzeugerP3"));

		$aktuelleErzeugungW		=	0;
		if ($this->ReadPropertyInteger("ErzeugerW1")>0) $aktuelleErzeugungW += getValue($this->ReadPropertyInteger("ErzeugerW1"));
		if ($this->ReadPropertyInteger("ErzeugerW2")>0) $aktuelleErzeugungW += getValue($this->ReadPropertyInteger("ErzeugerW2"));
		if ($this->ReadPropertyInteger("ErzeugerW3")>0) $aktuelleErzeugungW += getValue($this->ReadPropertyInteger("ErzeugerW3"));



		$bezogeneEnergie			= 	getValue($this->GetIDforIdent("bezogeneEnergie"));

		$eingespeisteEnergie		=	getValue($this->GetIDforIdent("eingespeisteEnergie"));

		$gespeicherteEnergie		=	getValue($this->GetIDforIdent("gespeicherteEnergie"));

		$direktverbrauchteEnergie	= 	getValue($this->GetIDforIdent("selbstverbrauchteEnergie"));

		$maxLadeleistung			= 	$this->ReadPropertyInteger("MaxLadeleistung");

		$kapazitaet					=	$this->ReadPropertyInteger("Kapazitaet")/1000;

		$fuellstand					=	getValue($this->GetIDforIdent("fuellstand"));


		// Berechnung der Leistungswerte
		if ($aktuellerVerbrauchP > $aktuelleErzeugungP) {
			if ($fuellstand <= 0) {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), max($aktuellerVerbrauchP - $aktuelleErzeugungP,0));
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), 0);
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), 0);
			} else {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), max($aktuellerVerbrauchP - $aktuelleErzeugungP - $maxLadeleistung,0));
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), max($aktuelleErzeugungP - $aktuellerVerbrauchP, -1*$maxLadeleistung));
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), 0);
			}
		} else {
			if ($fuellstand >= $kapazitaet) {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), 0);
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), 0);
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), max($aktuelleErzeugungP - $aktuellerVerbrauchP,0));
			} else {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), 0);
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), min($aktuelleErzeugungP - $aktuellerVerbrauchP, $maxLadeleistung));
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), max($aktuelleErzeugungP - $aktuellerVerbrauchP - $maxLadeleistung,0));
			}
		}



		// Berechnung, der Energiewerte
		if ($aktuellerVerbrauchW > $aktuelleErzeugungW) {
			if ($fuellstand <= 0) {
				setValue($this->GetIDforIdent("bezogeneEnergie"), $bezogeneEnergie + max($aktuellerVerbrauchW - $aktuelleErzeugungW,0));
				setValue($this->GetIDforIdent("fuellstand"), 0);
			} else {
				setValue($this->GetIDforIdent("bezogeneEnergie"), $bezogeneEnergie + max($aktuellerVerbrauchW - $aktuelleErzeugungW - $maxLadeleistung/60000,0));
				setValue($this->GetIDforIdent("fuellstand"), max($fuellstand + max($aktuelleErzeugungW - $aktuellerVerbrauchW, -1*$maxLadeleistung/60000), 0));
			}
		} else {
			if ($fuellstand >= $kapazitaet) {
				setValue($this->GetIDforIdent("eingespeisteEnergie"), $eingespeisteEnergie + max($aktuelleErzeugungW - $aktuellerVerbrauchW,0));
				setValue($this->GetIDforIdent("fuellstand"), $kapazitaet);
			} else {
				setValue($this->GetIDforIdent("eingespeisteEnergie"), $eingespeisteEnergie + max($aktuelleErzeugungW - $aktuellerVerbrauchW - $maxLadeleistung/60000,0));
				setValue($this->GetIDforIdent("fuellstand"), min($fuellstand + min($aktuelleErzeugungW - $aktuellerVerbrauchW, $maxLadeleistung/60000), $kapazitaet));
				setValue($this->GetIDforIdent("gespeicherteEnergie"), $gespeicherteEnergie + min($aktuelleErzeugungW - $aktuellerVerbrauchW, $maxLadeleistung/60000));
			}
		}


		SetValue($this->GetIDforIdent("fuellstandProzent"), round((getValue($this->GetIDforIdent("fuellstand"))*100 / $kapazitaet)/5)*5);

		SetValue($this->GetIDforIdent("aktuelleEigennutzung"), min($aktuellerVerbrauchP, $aktuelleErzeugungP));

		SetValue($this->GetIDforIdent("selbstverbrauchteEnergie"), $direktverbrauchteEnergie + min($aktuellerVerbrauchW, $aktuelleErzeugungW));


		if (Date("i", time()) == 00) {
			SetValue($this->GetIDforIdent("zyklen"), getValue($this->GetIDforIdent("gespeicherteEnergie")) / $kapazitaet);

			if (($bezogeneEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie)>0)
				SetValue($this->GetIDforIdent("EVGV"), ($direktverbrauchteEnergie + $gespeicherteEnergie)*100 / ($bezogeneEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie));

			if (($eingespeisteEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie)>0)
				SetValue($this->GetIDforIdent("EVGP"), ($direktverbrauchteEnergie + $gespeicherteEnergie)*100 / ($eingespeisteEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie));

			SetValue($this->GetIDforIdent("rollierendeEingespeisteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("eingespeisteEnergie")));
			SetValue($this->GetIDforIdent("rollierendeSelbstverbrauchteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("selbstverbrauchteEnergie")));
			SetValue($this->GetIDforIdent("rollierendeBezogeneEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("bezogeneEnergie")));
			SetValue($this->GetIDforIdent("rollierendeGespeicherteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("gespeicherteEnergie")));
			SetValue($this->GetIDforIdent("rollierendeEVGV"), $this->RollierenderJahreswert($this->GetIDforIdent("EVGV")));
			SetValue($this->GetIDforIdent("rollierendeEVGP"), $this->RollierenderJahreswert($this->GetIDforIdent("EVGP")));
			SetValue($this->GetIDforIdent("rollierendeZyklen"), $this->RollierenderJahreswert($this->GetIDforIdent("zyklen")));
		}
	}
 }
