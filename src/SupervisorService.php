<?php

use PhpXmlRpc\Client;
use PhpXmlRpc\Request;

class SupervisorService
{
    private string $host;
    private string $port;
    private string $username;
    private string $password;

    private Client $client;

    public function __construct(string $host, string $port, string $username, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->initClient();
    }

    /**
     * Initialize supervisor client
     */
    private function initClient()
    {
        // Set up supervisor connection
        $this->client = new Client($this->host . ':' . $this->port . '/RPC2');

        // Set credentials
        $this->client->setCredentials($this->username, $this->password);
    }
    /**
     * Get all processes from supervisor
     * @return array - ["result" => true, "data" => [], "error" => ""] (error is only set when result is false)
     */
    public function getProcesses(): array
    {
        try {
            
            // Create an XML-RPC request
            $request = new Request('supervisor.getAllProcessInfo');
            $response = $this->client->send($request);

            // Check for errors
            if ($response->faultCode()) {
                return [
                    'result' => false,
                    'error' => $response->faultString(),
                ];
            }
            // Get data
            $data = $response->serialize();

            // XML to Array
            $parsedArray = json_decode(json_encode(simplexml_load_string($data)), true);

            $result = $parsedArray['params']['param']['value']['array']['data']['value'] ?? [];

            // Check if result is an associative array, then convert it to a normal array
            if (!isset($result[1])) {
                $result = [$result];
            }

            /*
             * The array looks like this:
             * "struct" => [ "member" => [0 => [], 1 => [], ...] ]
             * We want to get rid of the "struct" and "member" keys
             * and just have a normal array with the processes
             */
            $result = array_map(
                function ($process){
                    foreach ($process['struct']['member'] ?? [] as $key => $value) {
                        $process[$value['name']] =  $value['value'][array_key_first($value['value'])];
                    }
                    unset($process['struct']);
                    return $process;
                },
                $result
            );

            return [
                'result' => true,
                'data' => $result
            ];
        }
        catch (\Exception $e) {
            return [
                'result' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


}
