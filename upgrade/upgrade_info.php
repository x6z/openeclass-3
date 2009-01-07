<?PHP header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META http-equiv=Content-Type content="text/html; charset=UTF-8">
<title>Οδηγίες Αναβάθμισης Open eClass 2.1</title>
<style type="text/css">

body {
 font-family: Verdana, Times New Roman;
  font-size: 12px;
  font-weight: normal;
 margin-right: 2em;
 margin-left: 2em;
}

p {
 line-height: 110%;
}

ol, ul {
 line-height: 140%;
}


h1, h2, h3, h4, h5 {
 font-weight: bold;
}

h2 {
  font-size: 19px;
}

h3 {
  font-size: 16px;
}

h4 {
  font-size: 13px;
}

pre {
 margin-left: 3em;
 padding: .5em;
}

.note {
 background-color: #E6E6E6;
}

</style>
<style type="text/css">
 li.c2 {list-style: none}
 div.c1 {text-align: center}
</style>
</head>
<body>
<h2>Οδηγίες Αναβάθμισης Open eClass 2.1</h2>
<h3>Διαδικασία Aναβάθμισης </h3>
<p>Η νέα έκδοση Open eClass 2.1 της πλατφόρμας διατηρεί τη συμβατότητα με τις προηγούμενες εκδόσεις. Για το λόγο αυτό μπορείτε εύκολα και γρήγορα να αναβαθμίσετε μια ήδη εγκατεστημένη πλατφόρμα eClass από τις προηγούμενες εκδόσεις (eClass 1.7 ή eClass 2.0) στην τρέχουσα, απλά ακολουθώντας τις οδηγίες αναβάθμισης που παραθέτουμε στη συνέχεια.</p>
<div class="note">
<p><b>ΠΡΟΣΟΧΗ!</b>
<ul>
<li>Αρχικά βεβαιωθείτε ότι κατά τη διάρκεια της αναβάθμισης δεν γίνονται μαθήματα ούτε υπάρχει πρόσβαση στις βάσεις δεδομένων της ήδη εγκατεστημένης πλατφόρμας eClass. 
</li>
<li>Ελέγξτε την έκδοση της ήδη εγκατεστημένης πλατφόρμας eClass ακολουθώντας το σύνδεσμο «Ταυτότητα Πλατφόρμας» στην Αρχική σελίδα. Για να είναι δυνατή η αναβάθμιση η ήδη εγκατεστημένη πλατφόρμα θα πρέπει να είναι έκδοση 1.7 ή 2.0 ή 2.0 beta. Αν είναι παλαιότερη έκδοση (1.5, 1.6) συνίσταται να την αναβαθμίστε πρώτα σε 1.7 ακολουθώντας τις οδηγίες στην παράγραφο «Αναβάθμιση από παλαιότερες εκδόσεις του eClass» του παρόντος, και στη συνέχεια αναβαθμίστε στην τρέχουσα Open eClass 2.1
</li>
<li>
Επίσης πριν ξεκινήσετε τη διαδικασία αναβάθμισης προτείνεται η λήψη αντίγραφου ασφαλείας των περιεχομένων των μαθημάτων και των βάσεων δεδομένων.
</li>
</ul>
</p>
</div>
<p>Επιγραμματικά για την αναβάθμιση της πλατφόρμας στη νέα έκδοση τα βήματα που πρέπει να ακολουθήσετε είναι τα εξής: 
<ul><b>Βήμα 1</b>
 <li> <a href="#unix">Αναβάθμιση σε υπολογιστές με λειτουργικό σύστημα Unix
    / Linux (Solaris, Redhat, Debian, Suse, Mandrake κ.λπ.)</a></li>
  <li><a href="#win">Αναβάθμιση σε υπολογιστές με λειτουργικό σύστημα Ms Windows
    (Windows2000, WindowsXP, Windows2003 κ.λπ.)</a></li>
	</ul>
	<ul><b>Βήμα 2</b>
  <li><a href="#dbase">Αναβάθμιση της Βάσης Δεδομένων</a></li>
	</ul>
	<ul><b>Βήμα 3</b>
  <li><a href="#after">Έλεγχος επιτυχούς αναβάθμισης</a></li>
	</ul>
	<ul><b>Βήμα 4</b>
  <li><a href="#other">Προαιρετικές επιπλέον ρυθμίσεις</a></li>
</ul>
<ul><b>Αναβάθμιση από παλιότερες εκδόσεις ( <= 1.6)</b>
	<li><a href="#oldest_unix">Για υπολογιστές με λειτουργικό σύστημα Unix / Linux</a></li>
	<li><a href="#oldest_win">Για υπολογιστές με λειτουργικό σύστημα Ms Windows</a></li>
	</ul>
<br>
<hr width='80%'><br>
<a name="unix">
<h3>Βήμα 1: Για υπολογιστές με λειτουργικό σύστημα Unix / Linux</h3>
</a>
<p>Όλες οι ενέργειες προϋποθέτουν ότι έχετε δικαιώματα διαχειριστή (root) στον εξυπηρετητή.</p>
<p>Το ακόλουθο παράδειγμα θεωρεί ότι η πλατφόρμα eClass είναι ήδη εγκατεστημένη
  στον κατάλογο <code>/var/www/html</code>.</p>
<p>Λόγω αρκετών αλλαγών στη καινούρια έκδοση (2.1 του Open eClass θα πρέπει να διαγράψετε
  την παλιά έκδοση και να εγκαταστήσετε την καινούρια. 
  Για να μην χαθούν οι παλιές σας ρυθμίσεις, θα πρέπει να κάνετε τα παρακάτω:</p>
<p>θεωρούμε ότι έχετε κατεβάσει το <b>openeclass-2.1.tar.gz</b> στο κατάλογο <code>/tmp
  </code></p>
<ul>
  <li>Μεταβείτε στον κατάλογο που έχετε εγκατεστημένο το eClass. π.χ.
    <pre>cd /var/www/html</pre>
  <li> Μετακινήστε το αρχείο των ρυθμίσεων (<em>eclass/config/config.php</em>)
    σε ένα άλλο προσωρινό κατάλογο. Μια καλή λύση είναι ο κατάλογος <em>/tmp</em>
    π.χ.
    <pre>mv /var/www/html/eclass/config/config.php /tmp</pre>
  </li>
  <li>Αν έχετε μαθήματα στα οποία έχετε χρησιμοποιήσει την λειτουργία "Κουβέντα",
    τότε μετακινήστε και τα αρχεία με τις προηγούμενες κουβέντες των μαθημάτων.
    Αυτά βρίσκονται στον κατάλογο eclass/modules/chat/ και έχουν την μορφή κωδικός_μαθήματος.chat.txt
    π.χ.
    <pre>mv /var/www/html/eclass/modules/chat/*.txt /tmp</pre>
  </li>
  <li>Διαγράψτε τους καταλόγους του μαθήματος εκτός των courses και config
     Π.χ.
    <pre>cd /var/www/html/eclass/
rm -rf images/ include/ info/ install/ manuals/ template/ modules/ </pre>
  </li>
  <li>Αποσυμπιέστε το <b>openeclass-2.1.tar.gz</b> σε ένα προσωρινό φάκελο (/tmp) π.χ.
    <pre>tar xzvf /tmp/openeclass-2.1.tar.gz</pre>

	Κατόπιν αντιγράψτε (copy) από τον προσωρινό φάκελο /tmp/openeclass21 όλα τα περιεχόμενα του 
	(δηλαδή αρχεία και φακέλους) στον κατάλογο της εγκατάστασης πχ.
	<pre>cp -a /tmp/openeclass21/*  /var/www/html/eclass/</pre>
	
	Με τον τρόπο αυτό, αντικαθίσταται ο φάκελος eclass, από αυτόν της νέας διανομής Open eClass 2.1.
  </li>
  <li>Μετακινήστε το αρχείο <em>config.php</em> στον κατάλογο <em>config</em>.
    π.χ.
    <pre>mv /tmp/config.php /var/www/html/eclass/config/</pre>
  <li>Επαναφέρετε και τα αρχεία με τις προηγούμενες κουβέντες στην αρχική τους
    θέση. π.χ.
    <pre>mv /tmp/*.txt /var/www/html/eclass/modules/chat/</pre>
  </li>
  <li>Διορθώστε (αν χρειάζεται) τα permissions των αρχείων και των υποκαταλόγων
    δίνοντας για παράδειγμα τις παρακάτω εντολές: (υποθέτοντας ότι ο user με τον
    οποίο τρέχει ο apache είναι ο www-data)
    <pre>cd /opt/eclass
chown -R www-data *
find ./ -type f -exec chmod 664 {} \;
find ./ -type d -exec chmod 775 {} \;
</pre>
  </li>
</ul>
<p>Μόλις ολοκληρωθούν τα παραπάνω, θα έχετε εγκαταστήσει με επιτυχία τα αρχεία
  της νέας έκδοσης του eClass (Open eClass 2.1). Στη συνέχεια μεταβείτε στο  <a href="#dbase">βήμα 2</a>
  για να αναβαθμίσετε τις βάσεις δεδομένων της πλατφόρμας.</p>

<h3><a name="win">Βήμα 1: Αναβάθμιση σε Υπολογιστές με Λειτουργικό Σύστημα Ms Windows</a></h3>
<p>Το ακόλουθο παράδειγμα προϋποθέτει ότι το eClass είναι ήδη εγκατεστημένο στον
  κατάλογο <code>C:\Program Files\Apache\htdocs\</code> και ότι έχετε κατεβάσει
  το <b>openeclass-2.1.zip</b>.</p>
<p>Λόγω αρκετών αλλαγών στη καινούρια έκδοση (2.1) του Open eClass θα πρέπει να διαγράψετε
  την παλιά έκδοση και να εγκαταστήσετε την καινούρια. Για να μην χαθούν όμως οι παλιές σας ρυθμίσεις και
  τα μαθήματα που έχουν δημιουργηθεί, θα πρέπει να κάνετε τα παρακάτω.</p>
<ul>
  <li>Μεταβείτε στον κατάλογο που έχετε εγκατεστημένο το eClass. π.χ. <code>C:\Program
    Files\Apache\htdocs</code></li>
  <li>Μετακινήστε το αρχείο των ρυθμίσεων <code>C:\Program Files\Apache\htdocs\eclass\claroline\include\config.php</code>
    σε ένα άλλο προσωρινό φάκελο στην επιφάνεια εργασίας. π.χ. από το <code>C:\Program
    Files\Apache\htdocs\eclass\claroline\include\</code> στο κατάλογο <code>C:\Documents
    and Settings\Administrator\Desktop\</code></li>
  <li>Αν έχετε μαθήματα στα οποία έχετε χρησιμοποιήσει την λειτουργία <em>"Κουβέντα"</em>
    τότε μετακινήστε και τα αρχεία με τις προηγούμενες κουβέντες των μαθημάτων.
    Αυτά βρίσκονται στον κατάλογο <code>C:\Program Files\Apache\htdocs\eclass\modules\chat\</code>
    και έχουν την μορφή κωδικός_μαθήματος.chat.txt</li>
  <li>Μπείτε στο κατάλογο που είναι εγκατεστημένο το eclass δηλαδή <code>C:\Program
    Files\Apache\htdocs\eclass\</code> και διαγράψτε τους καταλόγους <em>images, include, info, install, manuals, template, modules</em>
    μαζί με τους υποκαταλόγους τους.</li>
  <li>Αποσυμπιέστε το openeclass-2.1.zip σε ένα προσωρινό φάκελο στην επιφάνεια εργασίας.
    π.χ. <code>C:\Documents and Settings\Administrator\Desktop\eclass17</code>.
    Κατόπιν μετονομάστε τον προσωρινό φάκελο openeclass21 σε eclass και αντιγράψτε τον (copy) μαζί 
	με όλα τα περιεχόμενα του (δηλαδή αρχεία και φακέλους). Στη συνέχεια ανοίξτε το φάκελο 
	που περιέχει την εγκατάσταση του eClass, π.χ.  <code>C:\Program
    Files\Apache\htdocs\</code> και κάντε επικόλληση (paste). Με τον τρόπο αυτό,
    αντικαθίσταται ο φάκελος eclass, από αυτόν της νέας διανομής.
  </li>
  <li>Επαναφέρετε και τα αρχεία με τις προηγούμενες κουβέντες στην αρχική τους
    θέση δηλαδή στο <code>C:\Program Files\Apache\htdocs\eclass\modules\chat\</code></li>
  <li>Τέλος διαγράψτε το φάκελο στην επιφάνεια εργασίας όπου προσωρινά αποσυμπιέσαμε
    τη νέα διανομή.</li>
	</ul>
	<p>Μόλις ολοκληρωθούν τα παραπάνω θα έχετε εγκαταστήσει με επιτυχία τα αρχεία
    της νέας έκδοσης του Open eClass. Στη συνέχεια μεταβείτε στο  <a href="#dbase">βήμα 2</a>
    για να αναβαθμίσετε τις βάσεις δεδομένων του.</p>

<a name="dbase">
<h3>Βήμα 2: Αναβάθμιση της Βάσης Δεδομένων</h3>
</a>
<div class="note">
<p>Πριν τρέξετε το script αναβάθμισης της βάσης βεβαιωθείτε ότι η MySQL δεν λειτουργεί σε strict mode.
    Για να το διαπιστώσετε ελέγξτε αν έχει κάποια τιμή η παράμετρος
    <pre>--sql-mode</pre> η οποία βρίσκεται στο αρχείο ρυθμίσεων <em>my.cnf</em> ή <em>my.ini</em>
    για τους χρήστες UNIX και Windows αντίστοιχα. Αν έχει (π.χ. <code>--sql-mode=STRICT_TRANS_TABLES</code>
    ή <code>--sql-mode=STRICT_ALL_TABLES</code>) τότε αλλάξτε την σε κενή (<code>--sql-mode=""</code>).
    </p>
</div>
<div class="note">
  <p><b>Μόνο για συστήματα Unix/Linux: </b>Η διαδικασία αναβάθμισης περιλαμβάνει
    και κάποιες αλλαγές στο αρχείο ρυθμίσεων<em> config.php</em>. Επομένως μπορεί
    να χρειαστεί να αλλάξετε προσωρινά τα δικαιώματα πρόσβασης στο <em>config.php</em>.</p>
  </div>
<p>Πληκτρολογήστε στον browser σας το ακόλουθο URL:</p>
<code>http://(url του eclass)/upgrade/</code>
<p>Θα σας ζητηθεί το όνομα χρήστη (username) και συνθηματικό (password) του διαχειριστή
  της πλατφόρμας. Αφού δώσετε τα στοιχεία σας θα σας ζητηθεί να αλλάξετε / διορθώσετε
  τα στοιχεία επικοινωνίας. Κατόπιν θα αρχίσει η αναβάθμιση των βάσεων δεδομένων.
  Στην οθόνη σας θα δείτε διάφορα μηνύματα σχετικά με την πρόοδο της εργασίας.
  Φυσιολογικά δεν θα πρέπει να δείτε μηνύματα λάθους.
 Σημειώστε, ότι ανάλογα με τον αριθμό και το περιεχόμενο των μαθημάτων, είναι πιθανόν η διαδικασία να διαρκέσει αρκετά.
</p>
<p>Στην αντίθετη περίπτωση (αν δηλαδή εμφανιστούν μηνύματα λάθους) τότε πιθανόν
  να μην λειτουργήσει εντελώς σωστά κάποιο μάθημα. Τέτοια μηνύματα λάθους μπορεί
  να εμφανιστούν, αν έχετε τροποποιήσει τη δομή κάποιου πίνακα από τις βάσεις
  του eClass. Σημειώστε (αν είναι δυνατόν) το ακριβές μήνυμα λάθους που σας εμφανίστηκε.</p>
<p>Αν μετά την αναβάθμιση αντιμετωπίσετε προβλήματα με κάποιο μάθημα τότε επικοινωνήστε
  μαζί μας (<a href="mailto:admin@openeclass.org">admin@openeclass.org</a>).</p>
<a name="after">
<h3>Βήμα 3: Έλεγχος επιτυχούς αναβάθμισης</h3>
</a>
<p>Για να βεβαιωθείτε ότι η πλατφόρμα έχει αναβαθμιστεί, πηγαίνετε στο διαχειριστικό
  εργαλείο και επιλέξτε "Έκδοση της πλατφόρμας". Θα πρέπει να αναγράφεται
  η έκδοση <em>2.1</em>. Εναλλακτικά, από την αρχική σελίδα της πλατφόρμας, επιλέξτε
  το σύνδεσμο "Ταυτότητα Πλατφόρμας". Ανάμεσα στα άλλα θα αναγράφεται η έκδοση
  <i>2.1 </i>της πλατφόρμας.</p>

<p>Είστε έτοιμοι! Η διαδικασία αναβάθμισης έχει ολοκληρωθεί με επιτυχία! Για να δείτε τα καινούρια χαρακτηριστικά της νέας έκδοσης ανατρέξτε στο αρχείο
  κειμένου <a href="CHANGES.txt">CHANGES.txt</a>.Για επιπλέον προαιρετικές ρυθμίσεις (HTTPS, Latex κλπ) διαβάστε παρακάτω.</p>

<a name="other">
<h3>Προαιρετικές Επιπλέον Ρυθμίσεις</h3>
</a>
<ul><li>Στο αρχείο <em>config.php</em> θα έχει οριστεί η μεταβλητή <em>close_user_registration</em>
  η οποία εξ'ορισμού έχει τιμή <em>FALSE</em>. Αλλάζοντας την σε τιμή <em>TRUE</em>
  η εγγραφή χρηστών με δικαιώματα "φοιτητή" δεν θα είναι πλέον ελεύθερη. Οι χρήστες
  για να αποκτήσουν λογαριασμό στην πλατφόρμα θα ακολουθούν πλέον διαδικασία παρόμοια
  με τη δημιουργία λογαριασμού "καθηγητή" δηλαδή θα συμπληρώνουν μια φόρμα-αίτηση
  δημουργίας λογαριασμού φοιτητή. Η αίτηση εξετάζεται από τον διαχειριστή ο οποίος
  εγκρίνει την αίτηση, οπότε δημιουργεί τον λογαριασμό, ή την απορρίπτει. Αν δεν
  επιθυμείτε να αλλάξει ο τρόπος εγγραφής φοιτητών δεν χρειάζεται να την ορίσετε.
  </li>
  <li>Στο αρχείο <em>config.php</em> ορίζεται η μεταβλητή <em>have_latex</em>
      η οποία εξ'ορισμού έχει τιμή <em>FALSE</em>. Αλλάζοντας την σε τιμή <em>TRUΕ</em>
      θα έχετε υποστήριξη μαθηματικών συμβόλων σε ορισμένα υποσυστήματα του eClass.
      Αυτό όμως προϋποθέτει την ύπαρξη συστήματος latex στο σύστημα που φιλοξενεί
      το eClass. Περισσότερα για τις ρυθμίσεις που θα πρέπει να κάνετε, ανατρέξτε
      στο αρχείο κειμένου <a href="../install/README_latex.txt">README_latex.txt</a>. Αν δεν επιθυμείτε υποστήριξη latex
      αφήστε την όπως είναι (δηλαδή στην τιμή <em>FALSE</em>).
</li>
<li>
 Μπορείτε να προσθέσετε κείμενο ενημερωτικού περιεχομένου στα αριστερά και δεξιά της αρχικής σελίδας της πλατφόρμας. Για το σκοπό αυτό, απλά πληκτρολογήστε το κείμενο της αρεσκείας σας (σε μορφή HTML) στα scripts <em>eclass_home_extras_left.html</em> και <em>eclass_home_extras_right.html</em> αντίστοιχα, που βρίσκονται στον αρχικό κατάλογο του eClass.
</li>
<li>
 Μπορείτε να μετονομάσετε τα ονόματα των βασικών ρόλων των χρηστών της πλατφόρμας αλλάζοντας το αρχείο μηνυμάτων (path του eClass)/modules/lang/greek/common.inc.php και and (path του eClass)/modules/lang/english/common.inc.php
</li>

<li>Αν θέλετε να χρησιμοποιήσετε την πλατφόρμα με Web server που έχει ενεργοποιημένη την υποστήριξη SSL
      (π.χ. https://eclass.gunet.gr) μπορείτε να το κάνετε δηλώνοντας στο <em>config.php</em> την μεταβλητή
      <em>urlSecure</em>. π.χ. <code>$urlSecure = "https://eclass.gunet.gr"</code>. Περισσότερες και αναλυτικότερες
      οδηγίες για τις ενέργειες αυτές, μπορείτε να βρείτε στο εγχειρίδιο του Διαχειριστή (βρίσκεται μέσα στο διαχειριστικό εργαλείο).
    </li>
</ul>

<h3>Αναβάθμιση από παλιότερες εκδόσεις (<= 1.6) </h3>
<p>Για να κάνετε αναβάθμιση στην τρέχουσα έκδοση από παλαιότερες εκδόσεις χρειάζεται πρώτα αναβαθμίσετε την πλατφόρμα σας στην έκδοση 1.7. Ειδικότερα από την έκδοση 1.7 της πλατφόρμας, οι κατάλογοι των μαθημάτων αποθηκεύονται σε ένα καινούριο κατάλογο με όνομα <em>courses</em>. Επίσης έχει αλλάξει η τοποθεσία του αρχείου
  ρυθμίσεων (<code>config.php</code>), όπου πλέον βρίσκεται σε ένα καινούριο κατάλογο,
  με όνομα <em>config</em>. Για να μην χαθούν οι παλιές σας ρυθμίσεις και τα μαθήματα
  που έχουν δημιουργηθεί, θα πρέπει να κάνετε τα παρακάτω:</p>
<a name="oldest_unix">
<h4> Για υπολογιστές με λειτουργικό σύστημα Unix / Linux </h4>
</a>
<ul>
  <li>Μεταβείτε στον κατάλογο που έχετε εγκατεστημένο το eClass. π.χ.
    <pre>cd /var/www/html</pre>
  <li> Μετακινήστε το αρχείο των ρυθμίσεων (<em>eclass/claroline/include/config.php</em>)
    σε ένα άλλο προσωρινό κατάλογο. Μια καλή λύση είναι ο κατάλογος <em>/tmp</em>
    π.χ.
    <pre>mv /var/www/html/eclass/claroline/include/config.php /tmp</pre>
  </li>
  <li>Αν έχετε μαθήματα στα οποία έχετε χρησιμοποιήσει την λειτουργία "Κουβέντα",
    τότε μετακινήστε και τα αρχεία με τις προηγούμενες κουβέντες των μαθημάτων.
    Αυτά βρίσκονται στον κατάλογο eclass/claroline/chat/ και έχουν την μορφή κωδικός_μαθήματος.chat.txt
    π.χ.
    <pre>mv /var/www/html/eclass/claroline/chat/*.txt /tmp</pre>
  </li>
  <li>Διαγράψτε όλο τον κατάλογο claroline μαζί με όλους τους υποκαταλόγους και
    τα αρχεία κάτω από αυτόν. Π.χ.
    <pre>cd /var/www/html/eclass/
rm -rf claroline/</pre>
  </li>
  <li>Αποσυμπιέστε το eclass-1.7.3.tar.gz σε ένα προσωρινό φάκελο (/tmp) π.χ.
    <pre>tar xzvf /tmp/eclass-1.7.3.tar.gz
	</pre>
	Κατόπιν αντιγράψτε από τον προσωρινό φάκελο /tmp/openeclass21 όλα τα περιεχόμενα του 
	(δηλαδή αρχεία και φακέλους) στον κατάλογο της εγκατάστασης πχ.
	<pre>cp -a  /tmp/eclass17/*  /var/www/html/eclass/</pre>
		Με τον τρόπο αυτό, αντικαθίσταται ο φάκελος eclass, από αυτόν της νέας διανομής.
  </li>
  <li>Μπείτε στον κατάλογο εγκατάστασης του Cclass και δημιουργήστε τους καταλόγους
     <em>config</em> και <em>courses</em>. π.χ.
    <pre>cd /var/www/html/eclass
mkdir config
mkdir courses</pre>
  </li>
  <li>Μετακινήστε το αρχείο <em>config.php</em> στον κατάλογο <em>config</em>.
    π.χ.
    <pre>mv /tmp/config.php /var/www/html/eclass/config/</pre>
  <li>Μετακινήστε τους καταλόγους των μαθημάτων στον κατάλογο courses. (π.χ. αν
    έχουμε μαθήματα με κωδικούς ΤΜΑ100, ΤΜΑ101)
    <pre>cd /var/www/html/eclass
		mv TMA* ./courses/</pre>
  </li>
  <li>Επαναφέρετε και τα αρχεία με τις προηγούμενες κουβέντες στην αρχική τους
    θέση. π.χ.
    <pre>mv /tmp/*.txt /var/www/html/eclass/modules/chat/</pre>
  </li>
  <li>Διορθώστε (αν χρειάζεται) τα permissions των αρχείων και των υποκαταλόγων
    δίνοντας για παράδειγμα τις παρακάτω εντολές: (υποθέτοντας ότι ο user με τον
    οποίο τρέχει ο apache είναι ο www-data)
    <pre>cd /opt/eclass
chown -R www-data *
find ./ -type f -exec chmod 664 {} \;
find ./ -type d -exec chmod 775 {} \;
</pre>
  </li>
</ul>
<a name="oldest_win">
<h4> Για υπολογιστές με λειτουργικό σύστημα Ms Windows</h4>
</a>
<ul>
  <li>Μετακινήστε το υπάρχον αρχείο των ρυθμίσεων <code>C:\Program Files\Apache\htdocs\eclass\claroline\include\config.php</code>
    σε ένα άλλο προσωρινό φάκελο στην επιφάνεια εργασίας. π.χ. από το <code>C:\Program
    Files\Apache\htdocs\eclass\claroline\include\</code> στο κατάλογο <code>C:\Documents
    and Settings\Administrator\Desktop\</code></li>
  <li>Αν έχετε μαθήματα στα οποία έχετε χρησιμοποιήσει την λειτουργία <em>"Κουβέντα"</em>
    τότε μετακινήστε και τα αρχεία με τις προηγούμενες κουβέντες των μαθημάτων.
    Αυτά βρίσκονται στον κατάλογο <code>C:\Program Files\Apache\htdocs\eclass\claroline\chat\</code>
    και έχουν την μορφή κωδικός_μαθήματος.chat.txt</li>
  <li>Μπείτε στο κατάλογο που είναι εγκατεστημένο το eclass δηλαδή <code>C:\Program
    Files\Apache\htdocs\eclass\</code> και διαγράψτε τον κατάλογο <em>claroline</em>
    μαζί με τους υποκαταλόγους του.</li>
  <li>Αποσυμπιέστε το eclass-1.7.3.zip σε ένα προσωρινό φάκελο στην επιφάνεια εργασίας.
    π.χ. <code>C:\Documents and Settings\Administrator\Desktop\eclass17</code>.
    Κατόπιν μετονομάστε τον προσωρινό φάκελο eclass17 σε eclass και αντιγράψτε τον (copy) μαζί 
	με όλα τα περιεχόμενα του (δηλαδή αρχεία και φακέλους). Στη συνέχεια ανοίξτε το φάκελο 
	που περιέχει την εγκατάσταση του eClass, π.χ.  <code>C:\Program
    Files\Apache\htdocs\</code> και κάντε επικόλληση (paste). Με τον τρόπο αυτό,
    αντικαθίσταται ο φάκελος eclass, από αυτόν της νέας διανομής.</li>
  <li>Πηγαίνετε στον κατάλογο eclass και δημιουργήστε δύο νέους καταλόγους τον
    <em>config</em> και <em>courses</em>.</li>
  <li>Μετακινήστε το αρχείο config.php, στον κατάλογο config που μόλις δημιουργήσαμε.
    δηλαδή <code>C:\Program Files\Apache\htdocs\eclass\config\</code></li>
  <li>Μετακινήστε τους καταλόγους των μαθημάτων μέσα στο κατάλογο <em>courses</em>
    που δημουργήσατε προηγουμένως δηλαδή <code>C:\Program Files\Apache\htdocs\eclass\courses\</code>
  </li>
  <li>Επαναφέρετε και τα αρχεία με τις προηγούμενες κουβέντες στην αρχική τους
    θέση δηλαδή στο <code>C:\Program Files\Apache\htdocs\eclass\claroline\chat\</code></li>
  <li>Τέλος διαγράψτε το φάκελο στην επιφάνεια εργασίας όπου προσωρινά αποσυμπιέσαμε
    τη νέα διανομή.</li>
</ul>
