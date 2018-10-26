<?php
require_once 'EmailAddress.php';

class RR1Mail {
    private const MIME_BOUNDARY = '__BOUNDARY__';
    private $body_mimetype, $encoding;
    private $to, $from, $reply_to, $has_address;
    private $subject, $body, $attachment;

    public function __construct() {
        $this->body_mimetype = 'text/plain';
        $this->encoding = 'ISO-2022-JP';
        $this->has_address = false;
        $this->attachment = [];
    }

    public function set_option(string $body_mimetype, string $encoding = null) {
        $this->body_mimetype = $body_mimetype;

        if (!is_null($encoding)) {
            $this->encoding = $encoding;
        }
    }

    public function set_address(array $to, EmailAddress $from, EmailAddress $reply_to) {
        $this->to = EmailAddress::combine_address($to);
        $this->from = $from->format('name_addr');
        $this->reply_to = $reply_to->format('name_addr');

        $this->has_address = true;
    }

    public function set_body(string $body_text, string $subject = null) {
        $this->subject = $subject;
        $this->body = $body_text;
    }

    private function build_body() : string {
        $boundary = self::MIME_BOUNDARY;

        return <<<HEREDOC
--$boundary
Content-Type: {$this->body_mimetype}; charset=\"{$this->encoding}\"

{$this->body}

HEREDOC;
    }

    public function attach_file(string $filename, $data) {
        $this->attachment[] = [
            'filename'  => $filename,
            'data'      => $data
        ];
    }
    
    private function build_attachment() :string{
        $boundary = self::MIME_BOUNDARY;
        $attachment_body = '';

        foreach($this->attachment as $file) {
            $filedata = chunk_split(base64_encode($file['data']));
            $part_header = $this->build_part_header($file['filename']);

            $attachment_body .= <<<HEREDOC
--$boundary
$part_header

$filedata

HEREDOC;
        }
        $attachment_body .= "--$boundary--";

        return $attachment_body;
    }

    public function send() {
        if(!$this->has_address) {
            die('Address not set yet.');
        }

        // Prepare body and headers
        $headers        = $this->build_header();
        $message_body   = $this->build_body();
        $message_body   .= $this->build_attachment();

        if(DEBUG_LEVEL == 'info') {
            echo $headers.$message_body;
        }
        mail($this->to, $this->subject, $message_body, $headers) or die('Mail sending failed');
    }

    private function build_header(): string {
        $boundary = self::MIME_BOUNDARY;

        /*if(isset($this->reply_to)) {
            $header .= 'Reply-to: '.$this->reply_to.PHP_EOL;
        } else {
            $header .= 'Reply-to: '.PHP_EOL;
        }*/

        return <<<HEREDOC
From: {$this->from}
Reply-to: {$this->reply_to}
MIME-Version: 1.0
Content-Type: multipart/mixed;boundary="$boundary"

HEREDOC;
    }

    private function build_part_header(string $filename) : string {
        $ext = ['.html', '.htm'];

        $mimetype = 'application/octet-stream';    
        foreach($ext as $e) {
            if( $e == substr($filename, -strlen($e))) {
                $mimetype = 'text/html';
            }
        }

        return <<<HEREDOC
Content-Type: {$mimetype}; name="{$filename}"
Content-Disposition: attachment; filename="{$filename}"
Content-Transfer-Encoding: base64
HEREDOC;
    }
}