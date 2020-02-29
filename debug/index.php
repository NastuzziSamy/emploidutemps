<form method='get' action='/emploidutemps/debug/addiduv.php'>
  Ajouter un créneau à son edt avec le numéro d'id:
  <input name='idUV' type='number' />
  <input type='submit' />
</form>
<br />
<form method='get' action='/emploidutemps/debug/rmiduv.php'>
  Supprimer un créneau de son edt avec le numéro d'id:
  <input name='idUV' type='number' />
  <input type='submit' />
</form>
<br />
<form method='get' action='/emploidutemps/debug/rmrfuv.php'>
  Supprimer tous les créneaux de son edt d'une uv avec son nom:
  <input name='uv' type='string' />
  <input type='submit' />
</form>
<br />
Ici, je vous fais confiance pour modifier et créer des créneaux existants normalement.<br />
Evidemment, tout abus sera puni.
<form method='get' action='/emploidutemps/debug/uvctot.php'>
  Passer un cours en TP (des confusions ont été réalisées lors du traitement) via son numéro d'id:
  <input name='idUV' type='number' />
  <input type='submit' />
</form>
<br />
<form method='get' action='/emploidutemps/debug/createiduv.php'>
  Créer un créneau non existant (vérifiez bien qu'il n'existe pas !):
  <br />
  UV: <input name='uv' type='string' /><br />
  Type: <input type="radio" name="type" value="C" checked>Cours</radio>
  <input type="radio" name="type" value="D">TD</radio>
  <input type="radio" name="type" value="T">TP</radio><br />
  Jour: <input type="radio" name="day" value="0" checked>Lundi</radio>
  <input type="radio" name="day" value="1">Mardi</radio>
  <input type="radio" name="day" value="2">Mercredi</radio>
  <input type="radio" name="day" value="3">Jeudi</radio>
  <input type="radio" name="day" value="4">Vendredi</radio>
  <input type="radio" name="day" value="5">Samedi</radio>
  <input type="radio" name="day" value="6">Dimanche</radio><br />
  Groupe/numéro du créneau: <input name='group' type='number' /><br />
  Début (hh:mm): <input name='begin' type='string' /><br />
  Fin (hh:mm): <input name='end' type='string' /><br />
  Salle: <input name='room' type='string' /><br />
  Fréquence (toutes les n semaines): <input name='frequency' type='number' /><br />
  Semaine (A ou B ou rien ?): <input name='week' type='string' /><br />
  <input type='submit' />
</form>

<br />
<a href="/emploidutemps">Retourner</a>