<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/mdp.php'); // Récupération des données

class BDD extends PDO {

  public function __construct () {
    try { parent::__construct('mysql'.':host='.DB_HOST.'; dbname='.DB_NAME.'; charset=utf8', DB_USER, DB_PASS, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)); }
    catch (PDOException $e)  { BDD::meurt('__construct', $e); }
	}

	public function execute($s, $p)	{
		try { return $s->execute($p); }
		catch (PDOException $e) { BDD::meurt('execute', $e); }
	}

	public function exec($p) {
		try { return parent::exec($p); }
		catch (PDOException $e) { BDD::meurt('exec', $e); }
	}

	public function query($p)	{
		try { return parent::query($p); }
		catch (PDOException $e) { BDD::meurt('query', $e); }
	}

	private static function meurt($type, PDOException $e)	{
		$signature = date('Y/m/d H:i:s').'	'.$_SERVER['REMOTE_ADDR'];
    sendMail('simde@assos.utc.fr', 'Erreur dans la BDD', $signature.'	'.$type.'	'.$e->getMessage(), $from = 'emploidutemps@assos.utc.fr');
		die('Une erreur a été détectée et a été signalée.');
	}
}
?>
