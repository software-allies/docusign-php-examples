<?php

require_once('../vendor/autoload.php');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();


class DocuSignEmbeddedSignature
{
    private $loginAccount;
    private $apiClient;
    private $templateId;
    public $debug;

    public function __construct()
    {
        $this->debug=false;
        $this->templateId = getenv('DS_TEMPLATE_ID');  
    }


    public function authenticate() 
    {
        $username = getenv('DS_USERNAME');
        $password = getenv('DS_PASSWORD');
        $integratorKey = getenv('DS_KEY');
        
        if($this->debug) {
            echo "template id: {$templateId}<\br>";
        }


        // change to production (www.docusign.net) before going live
        $host = "https://demo.docusign.net/restapi";

        // create configuration object and configure custom auth header
        $config = new DocuSign\eSign\Configuration();
        $config->setHost($host);
        $config->addDefaultHeader("X-DocuSign-Authentication", "{\"Username\":\"" . $username . "\",\"Password\":\"" . $password . "\",\"IntegratorKey\":\"" . $integratorKey . "\"}");

        // instantiate a new docusign api client
        $this->apiClient = new DocuSign\eSign\ApiClient($config);
        $accountId = null;

        $authenticationApi = new DocuSign\eSign\Api\AuthenticationApi($this->apiClient);
        $options = new \DocuSign\eSign\Api\AuthenticationApi\LoginOptions();

        $loginInformation;

        try {
            
            $loginInformation = $authenticationApi->login($options);
       

            if(isset($loginInformation) && count($loginInformation) > 0)
            { 
                $this->loginAccount = $loginInformation->getLoginAccounts()[0];
                $host = $this->loginAccount->getBaseUrl();
                $host = explode("/v2",$host);
                $host = $host[0];

                // UPDATE configuration object
                $config->setHost($host);
        
                // instantiate a NEW docusign api client (that has the correct baseUrl/host)
                $this->apiClient = new DocuSign\eSign\ApiClient($config);
            }

        } catch (DocuSign\eSign\ApiException $ex)
        {
            echo "Authentication Exception: " . $ex->getMessage() . "\n";
        }

        if($this->debug) {
            echo "loginInformation: {$loginInformation}";
        }


        return  $loginInformation;

    }
    
    public function createEnvelopeWithEmbeddedRecipient()
    {
        $loginInformation = $this->authenticate();
        try 
        {
            if($this->debug) {
                echo "after authentication</br>";
            }
            if(isset($loginInformation))
            {
                $accountId = $this->loginAccount->getAccountId();

                if($this->debug) {
                    echo "accountId: {$accountId}</br>";
                }
                if(!empty($accountId))
                {
                    // set recipient information
                    $recipientName = "RECIPIENT NAME";
                    $recipientEmail = "jason+recipentname@softwareallies.com";

                        // configure the document we want signed
                    $documentFileName = "/var/www/test_signature_document.pdf";
                    $documentName = "test_signature_document.pdf";

                    // create envelope call is available in the EnvelopesApi
                    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($this->apiClient);

                        // Add a document to the envelope
                    $document = new DocuSign\eSign\Model\Document();
                    $document->setDocumentBase64(base64_encode(file_get_contents($documentFileName)));
                    $document->setName($documentName);
                    $document->setDocumentId("1");

                    //echo "Document {$document}";

                    // Create a |SignHere| tab somewhere on the document for the recipient to sign
                    $signHere = new \DocuSign\eSign\Model\SignHere();
                    $signHere->setXPosition("100");
                    $signHere->setYPosition("100");
                    $signHere->setDocumentId("1");
                    $signHere->setPageNumber("1");
                    $signHere->setRecipientId("1");

                    // add the signature tab to the envelope's list of tabs
                    $tabs = new DocuSign\eSign\Model\Tabs();
                    $tabs->setSignHereTabs(array($signHere));

                    // add a signer to the envelope
                    $signer = new \DocuSign\eSign\Model\Signer();
                    $signer->setEmail($recipientEmail);
                    $signer->setName($recipientName);
                    $signer->setRecipientId("1");
                    $signer->setTabs($tabs);
                    $signer->setClientUserId("1234");  // must set this to embed the recipient!


                    // Add a recipient to sign the document
                    $recipients = new DocuSign\eSign\Model\Recipients();
                    $recipients->setSigners(array($signer));
                    $envelop_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
                    $envelop_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");
                    
                    // set envelope status to "sent" to immediately send the signature request
                    $envelop_definition->setStatus("sent");
                    $envelop_definition->setRecipients($recipients);
                    $envelop_definition->setDocuments(array($document));

                    //echo "envelop defintion: {$envelop_definition}";
                    
                    // create and send the envelope! (aka signature request)
                    $envelop_summary = $envelopeApi->createEnvelope($accountId, $envelop_definition, null);

                    
                    if(!empty($envelop_summary))
                    {
                        if($this->debug) {
                            echo "$envelop_summary";

                            echo "envelop id: {$envelop_summary->getEnvelopeId()}<\br>";
                        }

                        $recipient_view_request = new \DocuSign\eSign\Model\RecipientViewRequest();
                        $recipient_view_request->setReturnUrl('http://localhost:8000/signedReturned.php');
                        $recipient_view_request->setClientUserId("1234");
                        $recipient_view_request->setAuthenticationMethod("email");
                        $recipient_view_request->setUserName($recipientName);
                        $recipient_view_request->setEmail($recipientEmail);

                        $signingView = $envelopeApi->createRecipientView($this->loginAccount->getAccountId(), $envelop_summary->getEnvelopeId(), $recipient_view_request);
                    
                        if(!empty($signingView))
                        {
                            if($this->debug) {
                                echo "Signing View URL {$signingView->getUrl()}<\br>";
                            } else {
                                header("location: {$signingView->getUrl()}");
                            }
                           
                        } else {
                            echo "Signing View Is Empty <\br>";
                        }
                    
                    }

                    
                }
            }
        }
        catch (DocuSign\eSign\ApiException $ex)
        {
            echo "Exception: " . $ex->getMessage() . "\n";
        }
    }

    public function createEnvelopeWithEmbeddedRecipientWithTemplate()
    {
        $loginInformation = $this->authenticate();
        try 
        {
            if($this->debug) {
                echo "after authentication</br>";
            }
            if(isset($loginInformation))
            {
                $accountId = $this->loginAccount->getAccountId();

                if($this->debug) {
                    echo "accountId: {$accountId}</br>";
                }
                if(!empty($accountId))
                {
                    // set recipient information
                    $recipientName = "John Doe";
                    $recipientEmail = "jason@softwareallies.com";

                    $textTab = new \DocuSign\eSign\Model\Text();
                    
                    // text field manually on docuSign site.
                    $textTab->setTabLabel("labelname");
                    $textTab->setValue('10000');
                  
                    $tabs = new DocuSign\eSign\Model\Tabs();
                    $tabs->setTextTabs(array($textTab));
                  
                    // create envelope call is available in the EnvelopesApi
                    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($this->apiClient);


                    $templateRole = new  DocuSign\eSign\Model\TemplateRole();
                    $templateRole->setEmail($recipientEmail);
                    $templateRole->setName($recipientName);
                    $templateRole->setRoleName("SignerRole"); 
                    $templateRole->setclientUserId("1234");
                    $templateRole->setTabs($tabs);

                    $envelop_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
                    $envelop_definition->setEmailSubject("Please sign this doc");
                    $envelop_definition->setTemplateId($this->templateId);
                    $envelop_definition->setTemplateRoles(array($templateRole));
                    

                    // set envelope status to "sent" to immediately send the signature request
                    $envelop_definition->setStatus("sent");

                    if($this->debug) {
                        echo "envelop defintion: {$envelop_definition}";
                    }
                    
                    // create and send the envelope! (aka signature request)
                    $envelop_summary = $envelopeApi->createEnvelope($accountId, $envelop_definition, null);

                    
                    if(!empty($envelop_summary))
                    {
                        if($this->debug) {
                            echo "$envelop_summary";

                            echo "envelop id: {$envelop_summary->getEnvelopeId()}<\br>";

                            //exit;
                        }

                        $recipient_view_request = new \DocuSign\eSign\Model\RecipientViewRequest();
                        $recipient_view_request->setReturnUrl('http://localhost:8000/signedReturned.php');
                        $recipient_view_request->setClientUserId("1234");
                        $recipient_view_request->setAuthenticationMethod("email");
                        $recipient_view_request->setUserName($recipientName);
                        $recipient_view_request->setEmail($recipientEmail);

                        $signingView = $envelopeApi->createRecipientView($this->loginAccount->getAccountId(), $envelop_summary->getEnvelopeId(), $recipient_view_request);
                    
                        if(!empty($signingView))
                        {
                            if($this->debug) {
                                echo "Signing View URL {$signingView->getUrl()}<\br>";
                            } else {
                                header("location: {$signingView->getUrl()}");
                            }
                           
                        } else {
                            echo "Signing View Is Empty <\br>";
                        }
                    
                    }

                    
                }
            }
        }
        catch (DocuSign\eSign\ApiException $ex)
        {
            echo "Exception: " . $ex->getMessage() . "\n";
        }
    }
    
    public function createRecipientView() {

    }
}

?>