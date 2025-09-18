<?php

function lire_emails($filename) {
    $file = fopen($filename, "r") or Die("Unable to open file");
    $emails =[];
    while (!feof($file)) {
        $line = trim(fgets($file)); //trim to remove spaces and \n
        if ($line !== "") { //if the ligne is empty
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
    return preg_match($pattern, $email); // compare with expression reguliere
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


lire_enregistrer_nonValides("Emails.txt"); // test d'enregister emails valides et non valides
supprimer_doublons("Emails_Valides.txt", "Emails_Valides_Uniques.txt"); // enlever doublons
trier_enregistrer("Emails_Valides_Uniques.txt", "EmailsT.txt"); // test de trier et enregister emails 
separer_par_domaine("EmailsT.txt"); // separer par domaine

?>