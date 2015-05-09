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
 * Created on 10 avr. 10
 * by LWR
 */

/*
		Cette fonction envoie un SMS $message vers $phone
		Le numéro de téléphone ($phone) doit être dans le format internationnal (+xx ou 00xx)
		Le message ($message) doit être encodé dans la table de caractère iso-8859-1
		Cette fonction retourne true en cas de succès ou false en cas d'erreur
		Ex: if (sendSms("+33600000000", "Hello world")) { print("Ok"); } else { print("Erreur"); }

*/
function sendSms($phone, $message) {
	$sock=fsockopen("admin.wigii.ch", 80);

	if ($sock) {
		$xml="<sms><key>0TuiKQFLD5ekRzh+tHbNjUIVfJ8uC9NqbXXmEwDAAgJoLHNdghZj0MmtIi85UrHy3X99lgjgz2BJcSAfZlZ0mv2v+FMu+V/ANmA1gGIrmGA</key><phone>" . str_replace(array("<", ">", "&"), array("&lt;", "&gt;", "&amp;"), $phone) . "</phone><message>" . str_replace(array("<", ">", "&"), array("&lt;", "&gt;", "&amp;"), $message) . "</message></sms>";
		$data="xml=" . urlencode($xml);
		fwrite($sock, "POST /sms/xmlrequest.php HTTP/1.0\r\nHost:admin.wigii.ch\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($data) . "\r\n\r\n" . $data);
		$answer=trim(fgets($sock, 1024));

		while (!empty($answer)) {
			$answer=trim(fgets($sock, 1024));
		}

		$answer=trim(fgets($sock, 1024));
		fclose($sock);
		return $answer=="ok";
	}

	return false;
}


?>
<!-- Exemple HTML -->
<!--
<?
/*		if (!empty($_POST["phone"]) && !empty($_POST["message"])) {
			sendSms($_POST["phone"], $_POST["message"]);
		}*/
?>
<html>
<body>
		<form action="<?=$_SERVER["SCRIPT_NAME"]?>" method="POST">
		Numéro de téléphone: <input type="text" name="phone"><br>
		Message: <textarea name="message"></textarea><br>
		<input type="submit" value="Envoyer">
		</form>
</body>
</html>
-->
