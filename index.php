<?php

function lire_emails($filename) {
    $file = fopen($filename, "r") or Die("Unable to open file");
    $emails =[];
    while (!feof($file)) {
        $line = trim(fgets($file)); // trim to remove spaces and \n
        if ($line !== "") { // if the ligne isn't empty
            $emails[] = strtolower($line);
        }
    }
    fclose($file);
    return $emails;
}

function write_emails($emails, $filename) {
    $file = fopen($filename , "w");
    foreach ($emails as $email) {
        fwrite($file, $email . "\n");
    }
    fclose($file);
}

function validate_email($email) {
    $pattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    return preg_match($pattern, $email); // checks if the email respect the regex
}

function lire_enregistrer_nonValides($filename) {
    $emails = lire_emails($filename);
    $emails_nonValid = [];
    $emails_valid = [];
    foreach ($emails as $email) {
        if (!validate_email($email)) {
            $emails_nonValid[] = $email;
        } else {
            $emails_valid [] = $email;
        }
    }
    write_emails($emails_valid, "Emails_Valides.txt");
    write_emails($emails_nonValid, "Emails_nonValides.txt");
    
}

function supprimer_doublons($file_old, $file_new) {
    $emails = lire_emails($file_old);
    $emails = array_unique($emails);
    write_emails($emails, $file_new);
}

function trier_enregistrer($file_old, $file_new){
    $emails = lire_emails($file_old);
    sort($emails);
    write_emails($emails, $file_new);
}

function separer_par_domaine($file_old){
    $emails = lire_emails($file_old);
    $domains = [];
    foreach ($emails as $email) {
        $parts = explode("@", $email);
        $domain = $parts[1];
        $domains[$domain][] = $email;
    }
    foreach ($domains as $domain => $list) {
        $filename = "domains/" . $domain . ".txt";
        write_emails($list, $filename);
    }
}

function email_exists($email, $filename) {
    $emails = lire_emails($filename);
    return in_array(strtolower($email), $emails);
}

function add_email($email, $filename) {
    $email = strtolower(trim($email));
    if (!validate_email($email)) {
        return "Adresse email invalide !";
    }
    if (email_exists($email, $filename)) {
        return "Cette adresse email existe déjà !";
    }

    $file = fopen($filename, "a");
    fwrite($file, $email . "\n");
    fclose($file);
    return "Adresse email ajoutée avec succès !";
}

// --------------------------------------------------
$output = "";
$add_email_message = "";

// Handle reset button
if (isset($_POST['reset_files'])) {
    $files_to_delete = ["EmailsT.txt", "Emails_Valides.txt", "Emails_Valides_Uniques.txt", "Emails_nonValides.txt"];
    foreach (glob("uploads/*.txt") as $f) {
        if (!in_array($f, $files_to_delete)) $files_to_delete[] = $f;
    }
    foreach (glob("domains/*.txt") as $f) {
        if (!in_array($f, $files_to_delete)) $files_to_delete[] = $f;
    }
    foreach ($files_to_delete as $f) {
        if (file_exists($f)) unlink($f);
    }
}

// Handle file upload
if (isset($_FILES['emailsFile']) && $_FILES['emailsFile']['error'] === 0) { //check if the upload was successful
    $uploadedFile = "uploads/" . basename($_FILES['emailsFile']['name']);
    if (move_uploaded_file($_FILES['emailsFile']['tmp_name'], $uploadedFile)) {
        // Process file
        lire_enregistrer_nonValides($uploadedFile);
        supprimer_doublons("Emails_Valides.txt", "Emails_Valides_Uniques.txt");
        trier_enregistrer("Emails_Valides_Uniques.txt", "EmailsT.txt");
        separer_par_domaine("EmailsT.txt");
    } else {
        $output .= "<p style='color:red;'>Erreur lors du déplacement du fichier.</p>";
    }
}

// Handle adding new email
if (isset($_POST['new_email'])) {
    $message = add_email($_POST['new_email'], "EmailsT.txt");

    if ($message === "Adresse email ajoutée avec succès !") {
        $add_email_message = "<span class='msg success'>$message</span>";
    } elseif ($message === "Cette adresse email existe déjà !") {
        $add_email_message = "<span class='msg warning'>$message</span>";
    } else {
        $add_email_message = "<span class='msg error'>$message</span>";
    }


    // update domain-separated file
    $parts = explode("@", strtolower(trim($_POST['new_email'])));
    $domain_file = "domains/" . $parts[1] . ".txt";
    $existing = file_exists($domain_file) ? lire_emails($domain_file) : [];
    $existing[] = strtolower(trim($_POST['new_email']));
    $existing = array_unique($existing);
    sort($existing);
    write_emails($existing, $domain_file);
}

// Display generated files
$fichiers = ["EmailsT.txt", "Emails_Valides.txt", "Emails_Valides_Uniques.txt", "Emails_nonValides.txt"];
$domain_files = glob("domains/*.txt");
$fichiers = array_merge($fichiers, $domain_files);

$files_html = "<ul>";
foreach ($fichiers as $f) {
    if (file_exists($f)) $files_html .= "<li><a href='$f' download>$f</a></li>";
}
$files_html .= "</ul>";
$output .= $files_html;

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Emails</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Gestion des Emails</h2>

        <!-- Upload form -->
        <form method="POST" enctype="multipart/form-data" class="form-block">
            <label>Choisissez votre fichier Emails.txt</label><br>
            <input type="file" name="emailsFile" accept=".txt" required><br>
            <button type="submit">Uploader et traiter</button>
        </form>

        <!-- Add email form -->
        <form method="POST" class="form-block">
            <label>Ajouter une nouvelle adresse email</label><br>
            <input type="email" name="new_email" placeholder="exemple@email.com" required>
            <div class="action-row">
                <button type="submit">Ajouter</button>
                <?php echo $add_email_message; ?>
            </div>
        </form>

        <h3>Fichiers générés</h3>
        
        <!-- Reset form -->
        <form method="POST" class="form-block">
            <button type="submit" name="reset_files" class="reset-btn">Réinitialiser tous les fichiers</button>
        </form>

        <!-- Display generated files -->
        <div class="output">
            <?php echo $output; ?>
        </div>
    </div>
</body>
</html>