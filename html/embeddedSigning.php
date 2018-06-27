<?php
//require "../class.DocuSignSample.php";
require "../class.DocuSignEmbeddedSignature.php";

//$docuSignSampleObject = new DocuSignSample;
//$docuSignSampleObject->signatureRequestFromTemplate();

$DocuSignEmbeddedSignatureObject = new DocuSignEmbeddedSignature;
$DocuSignEmbeddedSignatureObject->debug = false;
$DocuSignEmbeddedSignatureObject->createEnvelopeWithEmbeddedRecipientWithTemplate();


?>