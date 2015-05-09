<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/*
 * Created on 3 déc. 09
 * by LWR
 */
class Files extends DataTypeInstance{

	const Script_Extensions = "/^((sh)|(asp)|(cgi)|(php)|(php.*)|(phtm)|(phtml)|(htm.*)|(pl)|(pm)|(t)|(pod)|(htac.*)|(py)|(pyw)|(pyc)|(pyo)|(pyd))$/i";
	const mainSubField = "name"; // pour le focus (entre autre)

	/**
	 * ATTENTION!!!
	 * à faire attention pour le type de donnée fichier. Il ne faut jamais que le nom du champ
	 * de type File contienne des _. En effet nous utilisons un explode sur le _ pour obtenir le
	 * vrai nom du champ. Ceci dans ihnForm::checkValues
	 * champ
	 */

	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		//*********************************
		//pour finir on ne fait aucun check particulier... tout est fait directement dans
		//l'ihm checkValues
		//*********************************
		return;

//		//contrôle qu'en fichier à été uploader
//		//car les champs sont cachés et ils sont rempli par la fonction checkValue de l'ihmForm
//		//donc s'ils sont vides, en principe il n'y a pas eu d'upload
//		if((!$form->checkField || $get[$name . "_fileManager_check"] == "on") &&
//			(string)$form->fields[$formIndice]["require"]=="1" && (
//			$_POST[$name."_size"] == null || $_POST[$name."_path"]==null)){
//			$form->add_error_to_field($formIndice, "mustToUploadAtLeastOneFile");
//		} else {
//			//s'il ne sont pas vide, il y a eu peut-être une erreur dans l'upload du fichier
//			//il faut vérifier que le fichier existe sur le disque dur dans le cas où il y eu un nouvel
//			//upload
//			$idElement = (int)$form->fields[$formIndice]["values"]["idElement"];
//			$checkValidUploadFile = false; //indique s'il faut checker que l'upload du fichier c'est bien passé
//			if ($idElement != 0){
//				//indique que nous sommes dans une édition... par conséquent
//				//il faut voir s'il y a eu un nouveal upload (si le path c'est modifié...)
//				$query = " SELECT `path` FROM Files WHERE `id_element` = '$idElement' AND `field` = '$name' ";
//				$files = $q->execAndExtract($query);
//				//attention au slash ajouter dans le formulaire pour la comparaison du path...
//				$checkValidUploadFile = ($files[0]["path"]!=stripslashes(decode_value($_POST[$name."_path"])));
//			} else $checkValidUploadFile = true; //si idElement == 0, cela signifie que nous ajoutons un nouveau fichier, il faut donc contrôler que l'upload c'est bien passé
//
//			if ($checkValidUploadFile){
////				$uploaddir = str_replace("/web/", "/data/", dirname($_SERVER["SCRIPT_FILENAME"])."/")."modules/upload/tempUploadedFiles/";
////				$uploaddir = str_replace("/web\\", "/data/", $uploaddir);
////				$uploaddir = str_replace("\\web/", "/data/", $uploaddir);
////				$uploaddir = str_replace("\\web\\", "/data/", $uploaddir);
//				$uploaddir = dirname($_SERVER["SCRIPT_FILENAME"])."/".MODULE_PATH."upload/tempUploadedFiles/";
//				$uploaddir = str_replace("\\", "/", $uploaddir);
//				//il faut faire un stripslashes lorsque l'on utilise le path, car des slash sont ajouté venant du formulaire...
//				//eput($uploaddir.iconv("UTF-8", "ISO-8859-1", stripslashes($_POST[$name."_path"])));
//				//eput($_POST[$name."_path"]);
//				if (!file_exists($uploaddir.iconv("UTF-8", "ISO-8859-1", stripslashes(decode_value($_POST[$name."_path"]))))){
//					$form->add_error_to_field($formIndice, "errorInTheUploadingProcess");
//				}
//			}
//		}
//		//on ajoute le type Mime en fonction de l'extension du fichier
//		if ($_POST[$name."_type"]!=null){
//			$mimeType = typeMime($_POST[$name."_type"]);
//			$form->fields[$formIndice]["values"][$name."_mime"]=$mimeType;
//			$_POST[$name."_mime"]=$mimeType;
//		}
//		//pas de contrôle particulier autrement
	}

}


