<?php

function lire_emails($filename) {
    $file = fopen($filename, "r") or Die("Unable to open file");
    $emails =[];
    while (!feof($file)) {
        $line = trim(fgets($file)); // trim to remove spaces and \n
        if ($line !== "") { // if the ligne is empty
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
        $filename = $domain . ".txt";
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


// ----------------------------------------------------------------

if (isset($_FILES['emailsFile']) && $_FILES['emailsFile']['error'] === 0) {

    // Create uploads directory if it doesn't exist
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Set the destination path
    $uploadedFile = $uploadDir . basename($_FILES['emailsFile']['name']);

    // Move the uploaded file from temp to uploads folder
    if (move_uploaded_file($_FILES['emailsFile']['tmp_name'], $uploadedFile)) {

        echo "<p style='color:green;'>Fichier déplacé et prêt à être traité !</p>";

        // Now run all functions on the moved file
        lire_enregistrer_nonValides($uploadedFile);
        supprimer_doublons("Emails_Valides.txt", "Emails_Valides_Uniques.txt");
        trier_enregistrer("Emails_Valides_Uniques.txt", "EmailsT.txt");
        separer_par_domaine("EmailsT.txt");

        // Display generated files
        $fichiers = ["EmailsT.txt", "Emails_Valides.txt", "Emails_Valides_Uniques.txt", "Emails_nonValides.txt"];
        $emails = lire_emails("EmailsT.txt");
        foreach ($emails as $email) {
            $parts = explode("@", $email);
            $domain = $parts[1];
            $fichiers[] = $domain . ".txt";
        }
        $fichiers = array_unique($fichiers);

        echo "<h3>Fichiers générés :</h3><ul>";
        foreach ($fichiers as $f) {
            if (file_exists($f)) {
                echo "<li><a href='$f' download>$f</a></li>";
            }
        }
        echo "</ul>";

    } else {
        echo "<p style='color:red;'>Erreur lors du déplacement du fichier.</p>";
    }
}


?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Emails</title>
</head>
<body>
    <h2>Gestion des Emails</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Choisissez votre fichier Emails.txt :</label><br><br>
        <input type="file" name="emailsFile" accept=".txt" required><br><br>
        <button type="submit">Uploader et traiter</button>
    </form>
</body>
</html>
