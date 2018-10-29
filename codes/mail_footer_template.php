<?php
function body_footer(string $reply_to, string $sender_name) :string {
    return <<<HEREDOC
THIS IS AUTOMATED MESSAGE.

Any issue? Please REPLY to developper $reply_to.

from $sender_name.
HEREDOC;
}