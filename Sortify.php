<?php

/**
* Sortify est un gestionnaire de classement de fichiers en ligne de commande. 
* Il permet de scripter rapidement le classement automatique d'un grand nombre
* de fichiers.
* @author Sebastien Braun <sebastien.braun@troll-idees.com>
*/
class Sortify {
	
	/**
	* Chemin du dossier à trier
	*/
	var $_path = NULL;
	
	/**
	* Tableau des filtres enregistrés
	*/
	var $_filters;
	
	/**
	* Version
	*/
	var $version = '0.2';
	
	/**
	* Debug
	*/
	var $debug = false;	
	
	/**
	* Constructeur. Définis le dossier à surveiller (surveillance non récursive)
	* @param string $path	Chemin du dossier à trier
	*/
	public function __construct($path) {
		$this->watch($path);
		$this->_filters = array();
	}
	
	/**
	* Change le dossier à surveiller (surveillance non récursive)
	* @param string $path	Chemin du dossier à trier
	* @return boolean 
	*/
	public function watch($path) {
		// Nettoie un éventuel trailing slash		
		if (substr($path, -1) == '/')	$path = substr($path, 0, -1);
		if (!is_dir($path)) return false;
		
		$this->_path = $path;
		return true;
	}
	
	/**
	* Ajoute un filtre de tri et indique dans quel dossier déplacer les fichiers qui
	* correspondent au filtre.
	* @param SortifyFilter $filter	Filtre de tris
	* @param string $dest_path		Chemin du dossier de stockage
	*/
	public function addFilter($filter, $dest_path, $rename_masq = '') {
		// Nettoie un éventuel trailing slash
		if (substr($dest_path, -1) == '/') $dest_path = substr($dest_path, 0, -1);
		// Ajoute le slash du debut
		if ($rename_masq != '' && substr($rename_masq, 0, 1) !== '/') $rename_masq = '/'.$rename_masq;
		$this->_filters[] = array($filter, $dest_path, $rename_masq);
	}
	
	/**
	* Enlève tous les filtres précédemment définis
	*/
	public function clearFilter() {
		$this->_filters = array();	
	}
	
	/**
	* Lance le classement.
	* @return boolean|array	Retourne le liste des fichiers déplacés en cas de succès. Retourne false en cas d'erreur.
	*/
	public function scan() {
		if ($this->_path == NULL) return false;
		
		$return = array();
		
		// liste les fichiers
		// laisse passer les .app
		$dh = opendir($this->_path);
		if ($dh == false) return false;
		if ($this->debug) echo "Traitement du repertoire ".$this->_path." \n";
		while (($file = readdir($dh)) !== false) {
			
			$type = filetype($this->_path .'/'. $file);
			if ($file[0] == '.') continue;
			if (substr($file, -5) == '.part') continue; // ignore les fichiers partiels
			if ($type == 'dir' && substr($file, -4) != '.app') continue;
			if ($type != 'file' && $type != 'dir') continue;
	
			if ($this->debug) echo "Analyse du fichier ".$file." \n";	
			
			// Teste tous les filtres
			// Le premier qui reconnait le fichier le prends en charge
			foreach($this->_filters as $f) {
				if(!$f[0]->match($this->_path .'/'. $file)) continue;
				
				if ($this->debug) echo "Match par le filtre ".get_class($f[0])." \n";	
				
				$return[$this->_path .'/'. $file] = $this->_rename($this->_path .'/'. $file, $f[1], $f[2], $f[0]->getMasqInfo());
				
				break;
			}
		}
		closedir($dh);
		return $return;
	}
	
	private function _rename($file, $dest, $masque, $masque_data){
		
		if ($masque == '') {
			// retrouve le nom du fichier (basename)
			$masque = '/'.basename($file);
		}
		
		$masque = str_replace(array_keys($masque_data), $masque_data, $masque);
				
		if(!is_dir($dest.dirname($masque))) {
			if(!mkdir($dest.dirname($masque), 0770, true)) {
				if ($this->debug) echo "Makedir failed : ".$dest.dirname($masque)."\n";
				return false;
			}
		}
		
		if (rename($file, $dest.$masque)) {
			if ($this->debug) echo "Rename failed : ".$file." to ".$dest.$masque."\n";
			return $dest.$masque;
		} else {
			return false;
		}
	}

}


/**
* Interface des filtres à destination d'un objet Sortify. Les filtres sont chargé de 
* reconnaitre les fichiers qu'on leur présente. En cas de reconnaissance, ils peuvent
* extraire des informations suppélmentaires qui sont utilisable pour renommer les 
* fichiers. 
* @author Sebastien Braun <sebastien.braun@troll-idees.com>
*/
interface SortifyFilter {
	
	/**
	 * Passe un nom de fichier dans le filtre.
	 *
	 * @param string $path	Chemin du fichier
	 * @return boolean	Retourne true si le fichier est reconnu par le filtre. False sinon.
	 **/
	function match($path);

	/**
	 * Retourne les informations complémentaires sur le fichier reconnu. 
	 * Les données sont crées uniquement après un match réussit
	 *
	 * @return array Tableau associatif d'informations complementaires
	 **/
	function getMasqInfo();
}

?>