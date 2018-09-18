<?php

class RR1Mail {
    const MIME_BOUNDARY = '__BOUNDARY__';
    public function __construct() {

    }

    public function sendmail(array $address, string $subject = null, string $body_text = null, array $attach_files = null) {
        $boundary = self::MIME_BOUNDARY;

        // Prepare body and headers
        $to = $address['to'];
        $headers        = $this->build_header($address);
        $message_body   = <<<HEREDOC
--$boundary
Content-Type: text/plain; charset=\"ISO-2022-JP\"

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
    
        // echo $message_body;     // DEBUG
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