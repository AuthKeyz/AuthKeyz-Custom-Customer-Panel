<?php

namespace AuthKeyz;

session_start();

class api
{
    public $name, $OwnerId;

    public $numUsers, $numKeys, $numOnlineUsers, $customerPanelLink;

    function __construct($name, $OwnerId)
    {
        $this->name = $name;
        $this->OwnerId = $OwnerId;
    }

    function init()
    {
        if ($this->name == "" || $this->OwnerId == "") {
            die("Please set your application name, ownerid in credentials.php");
        }

        $data = array(
            "type" => "init",
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $response = $this->req($data);

        if ($response == "AuthKeyz_Invalid") {
            die("Application not found");
        }

        $json = json_decode($response);

        if ($json->message == "This program hash does not match, make sure you're using latest version") {
            $message = "This Application have hash check enabled, Please Disable it so you can access this.";
            die($message); // Dies + Prints Error Message.
        }

        if (!$json->success)
            die($json->message);
        else if ($json->success) {
            $_SESSION['sessionid'] = $json->sessionid;
            $this->numUsers = $json->appinfo->numUsers;
            $this->numKeys = $json->appinfo->numKeys;
            $this->numOnlineUsers = $json->appinfo->numOnlineUsers;
            $this->customerPanelLink = $json->appinfo->customerPanelLink;
        }
    }

    function login($username, $password, $code = null)
    {
        $data = array(
            "type" => "login",
            "username" => $username,
            "pass" => $password,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId,
            "code" => $code ? $code : null
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            unset($_SESSION['sessionid']);
            $this->error($json->message);
        } else if ($json->success)
            $_SESSION["user_data"] = (array)$json->info;

        return $json->success;
    }

    function register($username, $password, $key, $email = null)
    {
        $data = array(
            "type" => "register",
            "username" => $username,
            "pass" => $password,
            "key" => $key,
            "email" => $email,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            unset($_SESSION['sessionid']);
            $this->error($json->message);
        } else if ($json->success)
            $_SESSION["user_data"] = (array)$json->info;

        return $json->success;
    }

    function license($key, $code = null)
    {
        $data = array(
            "type" => "license",
            "key" => $key,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId,
            "code" => $code ? $code : null
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            unset($_SESSION['sessionid']);
            $this->error($json->message);
        } else if ($json->success)
            $_SESSION["user_data"] = (array)$json->info;

        return $json->success;
    }

    function upgrade($username, $key)
    {
        $data = array(
            "type" => "upgrade",
            "username" => $username,
            "key" => $key,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            unset($_SESSION['sessionid']);
            $this->error($json->message);
        }

        // don't allow them to dashboard yet, upgrade doesn't require password so they need to login after register

        return $json->success;
    }

    function var($varid)
    {
        $data = array(
            "type" => "var",
            "varid" => $varid,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            unset($_SESSION['sessionid']);
            $this->error($json->message);
        } else if ($json->success)
            return $json->message;
    }

    function log($message)
    {
        $User = gethostname();

        $data = array(
            "type" => "log",
            "pcuser" => $User,
            "message" => $message,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $this->req($data);
    }

    function setvar($varname, $data)
    {
        $data = array(
            "type" => "setvar",
            "var" => $varname,
            "data" => $data,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $this->req($data);
    }

    function getvar($varid)
    {
        $data = array(
            "type" => "getvar",
            "var" => $varid,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            return null;
        } else if ($json->success)
            return $json->response;
    }

    function webhook($webid, $param, $body = "", $conttype = "")
    {
        $data = array(
            "type" => "webhook",
            "webid" => $webid,
            "params" => $param,
            "body" => $body,
            "conttype" => $conttype,
            "sessionid" => $_SESSION['sessionid'],
            "name" => $this->name,
            "ownerid" => $this->OwnerId
        );

        $response = $this->req($data);

        $json = json_decode($response);

        if (!$json->success) {
            return null;
        } else if ($json->success)
            return $json->response;
    }

    public $public_key = "8e8d104f3ce9b29b411cfcf8e049d19fed42e63caa275e111bfa71ef3d237041";

    private function req($data)
    {
        $curl = curl_init("https://authkeyz.bond/api/1.3/");
        curl_setopt($curl, CURLOPT_USERAGENT, "AuthKeyz");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($curl);

        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($http_code == 429) {
            die("You're connecting too faster to loader, slow down");
        }

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_str = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($curl);

        $headers = array();
        foreach (explode("\r\n", $header_str) as $line) {
            $parts = explode(': ', $line, 2);
            if (isset($parts[1])) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        $this->sigCheck($body, $headers, $data['type']);

        $json = json_decode($body);
        if ($json && isset($json->ownerid) && $json->ownerid != $this->OwnerId) {
            die("Application mismatch. Terminating process.");
        }

        return $body;
    }

    private function sigCheck(string $resp, array $headers, string $type): void
    {
        $skipTypes = ['log', 'file', '2faenable', '2fadisable'];
        if (in_array($type, $skipTypes, true)) {
            return;
        }

        $signatureHeader = $headers['x-signature-ed25519'] ?? null;
        $timestampHeader = $headers['x-signature-timestamp'] ?? null;

        // 1. Asymmetric Ed25519 Verification (AuthKeyz API 1.3 Master Protocol)
        if (!empty($signatureHeader) && !empty($timestampHeader)) {
            if (!extension_loaded('sodium')) {
                die("The 'sodium' extension is required.");
            }

            if (!ctype_digit((string)$timestampHeader)) {
                $this->failSignatureCheck();
            }

            $timestamp = (int) $timestampHeader;
            if (abs(time() - $timestamp) > 300) {
                throw new \RuntimeException("Clock skew too large or request expired.");
            }

            $publicKeyHex = $this->public_key ?: '8e8d104f3ce9b29b411cfcf8e049d19fed42e63caa275e111bfa71ef3d237041';
            $signedMessage = (string)$timestampHeader . $resp;

            try {
                $signature = sodium_hex2bin($signatureHeader);
                $publicKey = sodium_hex2bin($publicKeyHex);

                if (!sodium_crypto_sign_verify_detached($signature, $signedMessage, $publicKey)) {
                    $this->failSignatureCheck();
                }
                return;
            } catch (\Throwable $e) {
                $this->failSignatureCheck();
            }
        }
    }

    private function failSignatureCheck(): void
    {
        http_response_code(401);
        exit('Signature verification failed.');
    }

    public function error($msg)
    {
        echo '
                <script type=\'text/javascript\'>
                
                const notyf = new Notyf();
                notyf
                  .error({
                    message: \'' . addslashes($msg) . '\',
                    duration: 3500,
                    dismissible: true
                  });                
                
                </script>
                ';
    }
}
?>
