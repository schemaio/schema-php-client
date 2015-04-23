<?php

namespace Schema;

class ConnectionException extends \Exception {}

class NetworkException extends ConnectionException {}

class ProtocolException extends ConnectionException {}

class ServerException extends ConnectionException {}

class Connection
{
    /**
     * @var bool
     */
    public $connected;

    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port;

    /**
     * @var bool
     */
    public $options;

    /**
     * @var resource
     */
    protected $stream;

    /**
     * @var string
     */
    protected $last_request;

    /**
     * @var int
     */
    protected $last_request_id;

    /**
     * @var int
     */
    public $request_count = 0;

    /**
     * @param  string $host
     * @param  string $port
     */
    public function __construct($options = null)
    {
        $this->options = $options ?: array();
        $this->connected = false;
    }

    /**
     * Connect to server
     *
     * @return void
     */
    public function connect()
    {
        $this->host = $this->options['host'];
        $this->port = $this->options['port'];
        
        if (isset($this->options['clear']) && $this->options['clear']) {
            $this->stream = stream_socket_client(
                "tcp://{$this->host}:{$this->port}", $error, $error_msg, 10
            );
        } else {
            $options = array(
                'ssl' => array(
                    'verify_peer' => false
                )
            );
            if ($this->options['verify_cert']) {
                $options['ssl']['verify_peer'] = true;
                $options['ssl']['verify_depth'] = 5;
                $options['ssl']['cafile'] = \dirname(\dirname(__FILE__)).'/data/ca-certificates.crt';
            }
            $context = stream_context_create($options);
            $this->stream = stream_socket_client(
                "tls://{$this->host}:{$this->port}", $error, $error_msg, 10,
                STREAM_CLIENT_CONNECT, $context
            );
        }
        if ($this->stream) {
            $this->connected = true;
        } else {
            $error_msg = $error_msg ?: 'Peer certificate rejected';
            throw new NetworkException(
                "Unable to connect to {$this->host}:{$this->port} "
                ."(Error:{$error} {$error_msg})"
            );
        }

        stream_set_blocking($this->stream, false);
    }

    /**
     * Initiate request
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function request($method, $args = array())
    {
        $this->request_write($method, $args);
        return $this->request_response();
    }

    /**
     * Write request to stream
     *
     * @param  string $method
     * @param  array $args
     */
    private function request_write($method, $args)
    {
        $req_id = $this->request_id(true);
        $request = json_encode(array($req_id, $method, $args))."\n";
        $this->last_request = $request;
        if (!$this->stream) {
            $desc = $this->request_description();
            throw new NetworkException("Unable to execute request ({$desc}): Connection closed");
        }
        for ($written = 0; $written < strlen($request); $written += $fwrite) {
            $fwrite = fwrite($this->stream, substr($request, $written));
            if ($fwrite === false) {
                break;
            }
        }
        $this->request_count++;
    }

    /**
     * Get server response
     *
     * @return mixed
     */
    private function request_response()
    {
        $response = '';
        while (true) {
            $buffer = fgets($this->stream);
            $response .= $buffer;
            if (strstr($buffer, "\n")) {
                break;
            } else {
                usleep(1000);
            }
        }

        $message = '';
        if (!$response) {
            $this->close();
            $desc = $this->request_description();
            throw new ProtocolException("Unable to read response from server ({$desc})");
        } else if (null === ($message = json_decode(trim($response), true))) {
            $desc = $this->request_description();
            throw new ProtocolException("Unable to parse response from server ({$desc}): {$response}");
        } else if (!is_array($message) || !is_array($message[1])) {
            $desc = $this->request_description();
            throw new ProtocolException("Invalid response from server ({$desc}): ".json_encode($message));
        }

        $id = $message[0];
        $data = $message[1];

        if (isset($data['$error'])) {
            throw new ServerException((string)$data['$error']);
        }
        if (isset($data['$end'])) {
            $this->close();
        }

        return $data;
    }

    /**
     * Get or reset unique request identifier
     *
     * @param  bool $reset
     * @return string
     */
    public function request_id($reset = false)
    {
        if ($reset) {
            $hash_id = openssl_random_pseudo_bytes(32);
            $this->last_request_id = md5($hash_id);
        }
        return $this->last_request_id;
    }

    /**
     * Get description of last request
     *
     * @return string
     */
    private function request_description()
    {
        $request = json_decode(trim($this->last_request), true);
        $desc = strtoupper($request[1]);
        if (isset($request[2][0])) {
            $desc .= ' '.json_encode($request[2][0]);
        }
        return $desc;
    }

    /**
     * Close connection stream
     *
     * @return void
     */
    public function close()
    {
        fclose($this->stream);
        $this->stream = null;
        $this->connected = false;
    }
}
