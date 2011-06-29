<?php

/**
* Filtre à destination d'un objet Sortify. Il est destiné à reconnaitre
* les fichiers numérotés comme les épisodes de séries, pour les renommer
* de façon normalisée. Fournis aussi un masque de renommage à partir des
* informations trouvées dans le nom de fichier (saison, n° d'épisode).
* @author Sebastien Braun <sebastien.braun@troll-idees.com>
*/
class SortifySeriesFilter implements SortifyFilter {
	
	var $mode;
	var $masque;
	
	/**
	* Constructeur
	*/
	function __construct(){
		$this->mode = 'explicite';
		$ths->masque = array();
	}
	
	/**
	 * Passe un nom de fichier dans le filtre.
	 *
	 * @param string $path	Chemin du fichier
	 * @return boolean	Retourne true si le fichier est reconnu par le filtre. False sinon.
	 **/
	function match($path){
		$ths->masque = array();		
		
		$info = pathinfo($path);
		
		// ignore les fichiers non video
		if(!in_array($info['extension'], array('avi', 'mkv', 'mp4', 'mov'))) return false;	
		
		// var_dump($this->words);
		
		// compare avec la liste de mots
		$find = false;
		foreach($this->words as $s) {
			if(stripos($info['filename'],$s) === false) continue;
			$find = true;
		}
		if (!$find) return false;

		switch($this->mode) {
			case 'absolu' :
					$r = $this->_findAbsoluteEpisode($info['filename']);
					break;
			case 'explicite' :
					$r = $this->_findExpliciteEpisode($info['filename']);
					break;
		}

		if ($r === false) {
			$this->error = "Detection du numéro d'épisode échoué";
			return false;
		}
		
		list($saison, $episode, $absolu) = $r;

		$this->masque = array(
					'{saison}'=>$saison,
					'{saison2}'=>str_pad($saison, 2, '0', STR_PAD_LEFT),
					'{episode}'=>$episode,
					'{ext}'=>$info['extension'],
					'{absolu}'=>$absolu
					);
		
		// TODO test présence d'un srt aussi
		
		return true;
	}

	/**
	 * Retourne les informations complémentaires sur le fichier reconnu. 
	 * Les données sont crées uniquement après un match réussit
	 *
	 * @return array Tableau associatif d'informations complementaires
	 **/
	function getMasqInfo(){
		return $this->masque;
	}
	
	function setWords($words) {
		
		$this->words = array();
		
		$words2 = str_replace(' ', '_', $words);
		if ($words2 !== $words) $this->words[] = $words2;
		
		$words2 = str_replace(' ', '.', $words);
		if ($words2 !== $words) $this->words[] = $words2;
		
		$this->words[] = $words;
	}
	
	/**
	* Test la configuration du renamer
	* @return boolean
	*/
	function test() {
		return is_dir($this->repository);	
	}
	
	function mapSaisons($saisons) {
		$this->saisons = $saisons;
		$this->mode = 'absolu';
	}

	function _findExpliciteEpisode($filename) {
		preg_match("/([0-9]{1,2})(x|E|e)([0-9]{1,3})/", $filename, $regs);
		if (count($regs) == 0) {
			return false;
		}

		return array((int) $regs[1], (int) $regs[3], (int) $regs[3]);
	}

	function _findAbsoluteEpisode($filename) {

		preg_match("/[0-9]{1,3}/", $filename, $regs);

		// impossible de trouver un numéro d'épisode
		if (count($regs) == 0) return false;

		$absolu = $regs[0];

		if (count($this->saisons) == 0) {
			$saison = 1;
			$episode = $absolu;
		} else {
			foreach($this->saisons as $s=>$first) {
				if ($first <= $absolu) {
					$saison = $s;
					$episode = $absolu-$first+1;
				}
			}
		}

		$episode = str_pad($episode, 2, '0', STR_PAD_LEFT);

		return array($saison, $episode, $absolu);
	}
	
}

?>