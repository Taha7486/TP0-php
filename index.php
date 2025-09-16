<?php
function lire_emails($filename) {
    $file = fopen($filename,"r")or Die("Unable to open file");
    $emails =[];
    while(!feof($file)){
        $line = trim(fgets($file));//trim to remove spaces and \n
        if ($line !=="") {//if the ligne is empty
            $emails[] = $line;
        }
    }
    fclose($file);
    return $emails;
}

function write_emails($emails , $filename) {
    $file = fopen($filename , "w");
    foreach ($emails as $email) {
        $email = $email."\n";
        fwrite($file,$email);
    }
    fclose($file);
    
}

function validate_email($email) {
    $pattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    if (preg_match($pattern, $email)) {// compare with expression reguliere
        return true ;
    } else {
        return false ;
    }
    
}

function lire_enregistrer_nonValides($filename) {
    $emails = lire_emails($filename);
    $emails_nonValid ;
    $emails_valid;
    foreach ($emails as $email) {
        if (!validate_email($email)) {
            $emails_nonValid[]=$email;
        }else {
            $emails_valid [] = $email;
        }
    }
    write_emails($emails_valid,"Emails_Valides.txt");
    write_emails($emails_nonValid,"Emails_nonValides.txt");
    
}

function trier_enregistrer($file_old,$file_new){
    $emails = lire_emails($file_old);
    sort($emails);
    write_emails($emails,$file_new);
}

$emails = lire_emails("Emails.txt");// test de lire
write_emails($emails,"test.txt");//test d'ecriture

trier_enregistrer("Emails.txt","Emails_Trier");//test de trier et enregister emails 

lire_enregistrer_nonValides("Emails.txt");//test d'enregister emails valides et non valides


?>