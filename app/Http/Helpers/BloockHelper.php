<?php
namespace App\Http\Helpers;

use Bloock\Bloock;
use Bloock\Client\IdentityClient;
use Bloock\Client\KeyClient;
use Bloock\Entity\IdentityV2\BjjIdentityKey;
use Bloock\Entity\IdentityV2\IdentityKeyArgs;
use Bloock\Entity\Key\KeyProtectionLevel;
use Bloock\Entity\Key\KeyType;
use Bloock\Entity\Key\ManagedKeyParams;
use GuzzleHttp\Client;
//use Http\Adapter\Guzzle6\Client;
use GuzzleHttp\Psr7\Request;
// Exception
use App\Exceptions\BloockException;


class BloockHelper extends Bloock {

    public function __construct(){
        $this::$apiKey = env("BLOOCK_API_KEY");
        //$this::$identityApiHost = getenv("https://identity-managed-api.bloock.com");
        $this::$identityApiHost = "https://identity-managed-api.bloock.com/";
    }
    /**
     * Create identity
     *
     * @return object
     */
    public function createIdentity(){
        $identityClient = new IdentityClient();
        $keyClient = new KeyClient();

        $keyProtection = KeyProtectionLevel::SOFTWARE;
        $keyType = KeyType::Bjj;

        $params = new ManagedKeyParams($keyProtection, $keyType);
        $key = $keyClient->newManagedKey($params);

        $identityKey = new BjjIdentityKey(new IdentityKeyArgs($key));

        $identity = $identityClient->createIdentity($identityKey, null);

        $data=(object)[];
        $data->key = $key->id;
        $data->did = $identity;

        return $data;
    }

    public function issuance($identity,$email){
        $digitalId=(object)[];
        $client = new Client;
        // Prepare Bloock Clients
        $identityClient = new IdentityClient();
        $keyClient = new KeyClient();

        $issuerManagedKey = "6816a91c-d8d3-4f25-b4e8-1684521ca2c8"; // Key ID of the issuer created
        $schemaId = "QmeMrM2Av59brxHK46kBYGsirgpBaMxQ1MXDMosDMtT6cf"; // Schema ID of the schema created

        // Create Credential with API
        $apiUrl = "https://identity-managed-api.bloock.com/v1/credentials?issuer_key=$issuerManagedKey";
        $data = [
            "schema_id" => $schemaId,
            "holder_did" => $identity->did, // Here should be $holder
            "credential_subject" => [
                [
                    "key" => "email",
                    "value" => $email
                ]
            ],
            "expiration" => 1735118054, // Wed Dec 25 2024 09:14:14 GMT+0000
            "version" => 0
        ];
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $request = new Request('POST', $apiUrl, $headers, json_encode($data));
        $response = $client->sendRequest($request);

        $credentialId = "";
        if ($response->getStatusCode() == 201) {
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);
            $credentialId = $data['id'];

            $digitalId->credentialId=$credentialId;
        } else {
            $statusCode = $response->getBody();
            throw new BloockException(['Error'=>json_decode($statusCode)]);
        }

        /*
        // Iniciate Offering and Redeem Credential with API
        $apiUrl = "https://identity-managed-api.bloock.com/v1/credentials/$credentialId/offer?issuer_key=$issuerManagedKey";
        $request = new Request('GET', $apiUrl, $headers);
        $response = $client->sendRequest($request);

        if ($response->getStatusCode() == 200) {
            $body = $response->getBody()->getContents();

           echo "Offering created: $body\n";
        } else {

            $statusCode = $response->getBody();
            throw new BloockException(['Error'=>json_decode($statusCode)]);
        }*/

        // Get Credential with API
        $apiUrl = "https://identity-managed-api.bloock.com/v1/credentials/$credentialId";
        $request = new Request('GET', $apiUrl, $headers);
        $response = $client->sendRequest($request);

        if ($response->getStatusCode() == 200) {
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);
           
            $digitalId->signature=$data['proof'][0]['signature'];
            $digitalId->issuer=$data['issuer'];
            $digitalId->date=$data['issuanceDate'];
        } else {
            $statusCode = $response->getStatusCode();
            throw new BloockException(['Error'=>json_decode($statusCode)]);
        }
        return $digitalId;
    }

}


