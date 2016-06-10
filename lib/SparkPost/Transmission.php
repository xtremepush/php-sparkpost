<?php

namespace SparkPost;

class Transmission extends Resource
{
    protected $endpoint = 'transmissions';
    protected $customHeaders = array();

    public function __construct(SparkPost $sparkpost)
    {
        parent::__construct($sparkpost, $endpoint);
    }

    public function fixBlindCarbonCopy($payload)
    {
        //TODO: Manage recipients. "Vincent Song <vincentsong@sparkpost.com>"
        
        $modifiedPayload = $payload;
        $bccList = &$modifiedPayload['bcc'];
        $recipientsList = &$modifiedPayload['recipients'];
        
        //Format: Original Recipient" <original.recipient@example.com>
        //if a name exists, then do "name" <email>. Otherwise, just do <email>
        if(isset($modifiedPayload['recipients'][0]['name']))
        {
            $originalRecipient = '"' . $modifiedPayload['recipients'][0]['name'] 
                . '" <' . $modifiedPayload['recipients'][0]['address'] . '>';
        } else {
            $originalRecipient = '<' . $modifiedPayload['recipients'][0]['address'] 
                . '>';
        }

        //loop through all BCC recipients
        if(isset($bccList)){
            foreach ($bccList as $bccRecipient) { 
                $newRecipient = [
                        'address' => $bccRecipient['address'],
                        'header_to' => $originalRecipient,
                ];
                array_push($recipientsList, $newRecipient);
            }
        }
        
        //Delete the BCC object/array
        unset($modifiedPayload['bcc']); 

        return $modifiedPayload;
    }

    public function fixCarbonCopy($payload)
    {
        $ccCustomHeadersList = "";
        $modifiedPayload = $payload;
        $ccList = &$modifiedPayload['cc'];
        $recipientsList = &$modifiedPayload['recipients'];
        
        //if a name exists, then do "name" <email>. Otherwise, just do <email>
        if(isset($modifiedPayload['recipients'][0]['name'])) {
            $originalRecipient = '"' . $modifiedPayload['recipients'][0]['name'] 
                . '" <' . $modifiedPayload['recipients'][0]['address'] . '>';
        } else {
            $originalRecipient = '<' . $modifiedPayload['recipients'][0]['address'] 
                . '<';
        }
        
        if(isset($ccList)){
             foreach ($ccList as $ccRecipient) {
                $newRecipient = [
                        'address' => $ccRecipient['address'],
                        'header_to' => $originalRecipient,
                ];

                //if name exists, then use "Name" <Email> format. Otherwise, just email will suffice. 
                if(isset($ccRecipient['name'])) {
                    $ccCustomHeadersList = $ccCustomHeadersList . ' "' . $ccRecipient['name'] 
                        . '" <' . $ccRecipient['address'] . '>,';
                } else {
                    $ccCustomHeadersList = $ccCustomHeadersList . ' ' . $ccRecipient['address'];
                }

            }   
            
            if(!empty($ccCustomHeadersList)){ //If there are CC'd people
                $this->customHeaders = array("CC" => $ccCustomHeadersList);
            } 
            //Edits customHeaders and adds array of CSV list of CC emails
            
        }
        
        //delete CC
        unset($modifiedPayload['cc']);
        
        return $modifiedPayload;
    }

    public function post($payload)
    {
        $modifiedPayload = $this->fixBlindCarbonCopy($payload); //Accounts for any BCCs
        $modifiedPayload = $this->fixCarbonCopy($modifiedPayload); //Accounts for any CCs
        return parent::post($modifiedPayload, $this->customHeaders);
    }
}

$testPayload = 
[
    'content' => [
        'from' => [
            'name' => 'Sparkpost Team',
            'email' => 'from@sparkpostbox.com',
        ],
        'subject' => 'First Mailing From PHP',
        'html' => '<html><body><h1>Congratulations, {{name}}!</h1><p>You just sent your very first mailing!</p></body></html>',
        'text' => 'Congratulations, {{name}}!! You just sent your very first mailing!',
    ],
    'substitution_data' => ['name' => 'YOUR_FIRST_NAME'],
    'recipients' => [
        [
            'address' => 'EMAIL_ADDRESS1',
            'name' => 'NAME_1'
        ],
    ],
    'bcc' => [
        [
            'address' => 'BCC_EMAIL_ADDRESS1',
            'name' => 'BCC_NAME1'
        ],
        [
            'address' => 'BCC_EMAIL_ADDRESS2',
            'name' => 'BCC_NAME2'
        ],
    ], 
    'cc' => [
        [
            'address' => 'CC_EMAIL_ADDRESS1',
            'name' => 'CC_NAME1'
        ],
        [
            'address' => 'CC_EMAIL_ADDRESS2',
            'name' => 'CC_NAME2'
        ],
        [
            'address' => 'CC_EMAIL_ADDRESS3',
        ]
    ]
];
$transmission = new Transmission();
$transmission->post($testPayload);

?>