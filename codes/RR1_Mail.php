<?php
class RR1Mail {
    private const MIME_BOUNDARY = '__BOUNDARY__';
    private $body_mimetype, $encoding;

    public function __construct() {
        $this->body_mimetype = 'text/plain';
        $this->encoding = 'ISO-2022-JP';
    }

    public function set_option(string $body_mimetype, string $encoding = null) {
        $this->body_mimetype = $body_mimetype;

        if (!is_null($encoding)) {
            $this->encoding = $encoding;
        }
    }

    public function sendmail(array $address, string $subject = null, string $body_text = null, array $attach_files = null) {
        $boundary = self::MIME_BOUNDARY;

        // Prepare body and headers
        $to = $address['to'];
        $headers        = $this->build_header($address);
        $message_body   = <<<HEREDOC
--$boundary
Content-Type: {$this->body_mimetype}; charset=\"{$this->encoding}\"

$body_text

HEREDOC;

        // Attach file to mail
        foreach($attach_files as $file) {
            $filename = $file['filename'];
            $filedata = chunk_split(base64_encode($file['data']));

            $message_body .= <<<HEREDOC
--$boundary
Content-Type: application/octet-stream; name="{$filename}"
Content-Disposition: attachment; filename="{$filename}"
Content-Transfer-Encoding: base64

$filedata

HEREDOC;
        }
        $message_body .= "--$boundary--";
    
        //echo $headers.$message_body;     // DEBUG
        mail($to, $subject, $message_body, $headers) or die('Mail sending failed');
    }

    private function build_header(array $address): string {   // PHPv7
        $boundary = self::MIME_BOUNDARY;

        // Validate Address
        if(!isset($address['to'])) {
            die('To: address not specifyed.');
        }
        if(!isset($address['from'])) {
            die('From: address not specifyed.');
        }

        //$to_header	    = 'To: '.$address['to'];
        $from_header	= 'From: '.$address['from'];

        if(isset($address['reply_to'])) {
            $reply_to_header    = 'Reply-to: '.$address['reply_to'];
        } else {
            $reply_to_header    ='Reply-to: ';
        }

        return <<<HEREDOC
$from_header
$reply_to_header
MIME-Version: 1.0
Content-Type: multipart/mixed;boundary="$boundary"

HEREDOC;
    }
}
?>