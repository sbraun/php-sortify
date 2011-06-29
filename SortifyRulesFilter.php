<?php

/**
* Filtre à destination d'un objet Sortify. C'est un agrégat de règles préconstruites qui peuvent être personnalisées
* et cumulées à l'envie selon un système de règles obligatoires et optionnelles. Si toutes les règles obligatoires sont 
* validée au qu'au moins une règle optionnelle l'est, le filtre valide le ficher. On peux schématiser le rapport logique suivant : 
* REGLE OBLIGATOIRE 1 [ AND REGLE OBLIGATOIRE N [ AND ( REGLE OPTIONNELLE 1 [ OR REGLE OPTIONNEL N ] ) ]
* @author Sebastien Braun <sebastien.braun@troll-idees.com>
*/
class SortifyRulesFilter implements SortifyFilter {

	/**
	* Tableau des règles obligatoires
	*/
	var $_rules_m;
	
	/**
	* Tableau des règles optionnelles
	*/	
	var $_rules_o;
	
	/**
	* Tableau des grilles de types de fichiers
	*/
	var $_grilleTypes;
	
	/**
	* Constructeur
	*/
	function __construct() {
		$this->clearRules();
		
		// construction de la grille des types
		$this->_grilleTypes = array();
		$this->_grilleTypes['image'] = array(
										'jpeg'=>true,
										'jpg'=>true,
										'gif'=>true,
										'png'=>true,
										'bmp'=>true,
										'tif'=>true		
									);
									
		$this->_grilleTypes['archive'] = array(
										'zip'=>true,
										'rar'=>true,
										'tar'=>true,
										'gz'=>true,
										'7z'=>true,
										'bz2'=>true,
										'sitx'=>true	
									);
									
		$this->_grilleTypes['audio'] = array(
										'mp3'=>true,
										'wav'=>true,
										'ogg'=>true,
										'wma'=>true
									);
									
		$this->_grilleTypes['video'] = array(
										'mkv'=>true,
										'avi'=>true,
										'mov'=>true,
										'wmv'=>true,
										'mpg'=>true,
										'mpeg'=>true
									);
		
		$this->_grilleTypes['doc'] = array(
										'doc'=>true,
										'docx'=>true,
										'rtx'=>true,
										'pps'=>true,
										'ppt'=>true,
										'ppsx'=>true,
										'pptx'=>true,
										'sxw'=>true,
										'sxi'=>true,
										'pdf'=>true
									);
									
		$this->_grilleTypes['tableur'] = array(
										'xls'=>true,
										'xlsx'=>true,
										'csv'=>true,
										'sxc'=>true,
									);
	}
	
	/**
	* Supprime toutes les règles précédemment définies
	*/
	public function clearRules() {
		$this->_rules_m = array();
		$this->_rules_o = array();
	}
	
	/**
	* Ajoute une règle sur la date de modification du fichier. La règle s'active pour les fichiers dont la
	* date de modificaiton est plus vieille que la durée indiquée.
	* @param int $nb		Quantitié
	* @param string $unit	Unités (secondes par défaut). Supporte les valeurs suivantes : 
	*						s (secondes), i (minutes), h (heures)
	*						d (jour), m (mois), y (année)
	* @param boolean $mandatory	True signifie que la règle est obligatoire. False signifie que la règle est optionnelle
	*/
	public function addAgeRule($nb, $unit, $mandatory = true) {
		$nb = (int) $nb;
		switch($unit) {
			case 'y' :
						$nb *= 365;
			case 'm' :
						$nb *= 31;
			case 'd' :
						$nb *= 24;
			case 'h' :
						$nb *= 60;
			case 'i' :
						$nb *= 60;
			case 's' :
						break;
			default :
						return false;
		}
		if ($mandatory) {
			$this->_rules_m[] = array('_matchAgeRule', $nb, $unit);	
		} else {
			$this->_rules_o[] = array('_matchAgeRule', $nb, $unit);			
		}
	}
	
	/**
	* Ajoute une règle sur l'extenstion du fichier
	* @param string $ext	Extenstion
	* @param boolean $mandatory	True signifie que la règle est obligatoire. False signifie que la règle est optionnelle
	*/
	public function addExtRule($ext, $mandatory = true) {
		if ($mandatory) {
			$this->_rules_m[] = array('_matchExtRule', $ext);
		} else {
			$this->_rules_o[] = array('_matchExtRule', $ext);			
		}
	
	}	

	/**
	* Ajoute une règle sur une expression régulière (preg_match) qui sera appliqué sur le nom du fichier (sans le chemin)
	* @param string $reg	Expression réguliere compatible PREG
	* @param boolean $mandatory	True signifie que la règle est obligatoire. False signifie que la règle est optionnelle
	*/
	public function addRegExpRule($reg, $mandatory = true) {
		if ($mandatory) {
			$this->_rules_m[] = array('_matchRegExpRule', $reg);
		} else {
			$this->_rules_o[] = array('_matchRegExpRule', $reg);			
		}
	}	

	/**
	* Ajoute une règle sur le type de fichier
	* @param string $type	Type de fichier
	* @param boolean $mandatory	True signifie que la règle est obligatoire. False signifie que la règle est optionnelle
	*/
	public function addTypeRule($type, $mandatory = true) {
		if ($mandatory) {
			$this->_rules_m[] = array('_matchTypeRule', $type);
		} else {
			$this->_rules_o[] = array('_matchTypeRule', $type);			
		}
	}
	
	/**
	* Ajoute une règle sur le poids du fichier
	* @param string $weight	Poids du fichier (Ko par défaut). Il est possible d'indiquer les unités suivantes : K (ko), 
	*						M (Mo), G (Go). Ainsi "52M" équivaux à "les fichiers dont le poids font au moins 52 Mo.
	* @param boolean $mandatory	True signifie que la règle est obligatoire. False signifie que la règle est optionnelle
	*/
	public function addWeightRule($weight, $mandatory = true) {
		switch(substr($weight, -1)) {
			case 'G':
					$weight *= 1024;
			case 'M':
					$weight *= 1024;
			case 'K':
			default :
					$weight *= 1024;
					break;
		}
		
		$weight = (int) $weight;
		
		if ($mandatory) {
			$this->_rules_m[] = array('_matchWeightRule', $weight);
		} else {
			$this->_rules_o[] = array('_matchWeightRule', $weight);			
		}	
	}	
	
	/**
	* Applique les règle au fichier. Si toutes les règles obligatoires sont validée au qu'au moins une règle 
	* optionnelle l'est, le filtre valide le ficher. On peux schématiser le rapport logique suivant : 
	* REGLE OBLIGATOIRE 1 [ AND REGLE OBLIGATOIRE N [ AND ( REGLE OPTIONNELLE 1 [ OR REGLE OPTIONNEL N ] ) ]
	* @param $file	Chemin du fichier à évaluer
	* @return boolean Retourne true si le filtre correspond au fichier. Retourne false dans le cas contraire
	*/
	public function match($file) {
		$match = false;
		// Appel des callback des regles obligatoires
		// TOUTES les règles doivent passer
		foreach($this->_rules_m as $r) {
			$h = array($this, $r[0]);
			$r[0] = $file;
			if(!call_user_func($h, $r)) return false;
		}
		
		if (count($this->_rules_o) == 0) return true;
		
		// Appel des callback des regles optionnelles
		// AU MOINS UNE règles doivent passer
		foreach($this->_rules_o as $r) {
			$h = array($this, $r[0]);
			$r[0] = $file;
			if(call_user_func($h, $r)) return true;
		}
		
		return false;
	}
	
	function getMasqInfo() {
		return array();
	}
	
	/**
	* Teste la règle de date de dernière modification
	* @param array $args Arguments. Le premier élément du tableau est toujours le chemin du fichier à tester
	* @return boolean Retourne true si la règle correspond au fichier. Retourne false dans le cas contraire.
	*/
	private function _matchAgeRule($args) {
		//echo "CHECK ".$args[0]."\n";
		return ((time()-filemtime($args[0])) > $args[1]);	
	}

	/**
	* Teste la règle de vérification de l'extenstion
	* @param array $args Arguments. Le premier élément du tableau est toujours le chemin du fichier à tester
	* @return boolean Retourne true si la règle correspond au fichier. Retourne false dans le cas contraire.
	*/
	private function _matchExtRule($args) {
		$ext = explode('.', $args[0]);
		// echo "EXT : ".array_pop($ext)." \n";
		return (array_pop($ext) === $args[1]);
	}

	/**
	* Teste la règle de vérification du nom par expression regulière
	* @param array $args Arguments. Le premier élément du tableau est toujuors le chemin du fichier à tester
	* @return boolean Retourne true si la règle correspond au fichier. Retourne false dans le cas contraire.
	*/
	private function _matchRegExpRule($args) {
		return preg_match($args[1],basename($args[0]));
	}

	/**
	* Teste la règle de type de fichier
	* @param array $args Arguments. Le premier élément du tableau est toujuors le chemin du fichier à tester
	* @return boolean Retourne true si la règle correspond au fichier. Retourne false dans le cas contraire.
	*/
	private function _matchTypeRule($args) {
		$ext = explode('.', $args[0]);
		$ext = array_pop($ext);
		//echo $ext."\n";
		//var_dump($args[1],$this->_grilleTypes[$args[1]]);
		return isset($this->_grilleTypes[$args[1]][$ext]);
	}

	/**
	* Teste la règle du poids de fichier
	* @param array $args Arguments. Le premier élément du tableau est toujors le chemin du fichier à tester
	* @return boolean Retourne true si la règle correspond au fichier. Retourne false dans le cas contraire.
	*/
	private function _matchWeightRule($args) {
		return (filesize($args[0]) >= $args[1]);
	}

}

?>