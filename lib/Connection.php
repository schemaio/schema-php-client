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
     * Activate the connection
     *
     * @return void
     */
    public function connect()
    {
        $this->host = $this->options['host'];
        $this->port = $this->options['port'];
        
        if ($this->options['clear']) {
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
    }

    /**
     * Request a server method
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function request($method, $args = array())
    {
        if (!$this->stream) {
            throw new NetworkException("Unable to execute '{$method}' (Error: Connection closed)");
        }

        $this->request_write($method, $args);

        return $this->request_response();
    }

    /**
     * Write a server request
     *
     * @param  string $method
     * @param  array $args
     */
    private function request_write($method, $args)
    {
        $req_id = $this->request_id(true);
        $request = array($req_id, $method, $args);
        fwrite($this->stream, json_encode($request)."\n");
        $this->request_count++;
    }

    /**
     * Get a server response
     *
     * @return mixed
     */
    private function request_response()
    {
        // Block until server responds
        if (false === ($response = fgets($this->stream))) {
            $this->close();
            throw new ProtocolException("Unable to read response from server");
        }

        if (null === ($message = json_decode(trim($response), true))) {
            throw new ProtocolException("Unable to parse response from server ({$response})");
        } else if (!is_array($message) || !is_array($message[1])) {
            throw new ProtocolException("Invalid response from server (".json_encode($message).")");
        }

        $id = $message[0]; // Not used since response is blocking
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
     * Get or create a unique request identifier
     *
     * @param  bool $reset
     * @return string
     */
    function request_id($reset = false)
    {
        if ($reset) {
            $hash_id = openssl_random_pseudo_bytes(32);
            $this->last_request_id = md5($hash_id);
        }
        return $this->last_request_id;
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
