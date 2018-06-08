<?php
namespace Monsoon;
use \Monsoon\Utils;

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Import Dompdf namespace.
use Dompdf\Dompdf;

/**
 * Object that sends email with attachments using PHPMailer.
 */
class Mailer
{
    /**
     * Import the utils.
     */
    use Utils;

    /**
     * Location for uploaded files.
     * @var string
     */
    protected $uploaddir;

    /**
     * Max file size for uploading.
     * @var int
     */
    protected $maxsize;

    /**
     * Permitted extensions for uploading.
     * @var array
     */
    protected $whitelist = [];

    /**
     * Contruct essential data.
     * @param [string] $uploaddir
     */
    public function __construct(
        $uploaddir,
        $maxsize = 2,
        $whitelist = []
    ) {
        $this->uploaddir = $uploaddir;
        $this->maxsize = $maxsize;
        $this->whitelist = $whitelist;
    }

    /**
     * Send email with attachments with a pdf for printing the user data.
     * @param  array  $emailsTo
     * @param  array  $emailsCc
     * @param  array  $emailsBcc
     * @return string or exception
     */
    public function sendMail(
        $emailsTo = [],
        $emailsCc = [],
        $emailsBcc = []
    ) {
        // Implemented a check, comparing CONTENT_LENGTH and post_max_size.
        $maxPostSize = $this->iniGetBytes('post_max_size');
        if ($_SERVER['CONTENT_LENGTH'] > $maxPostSize) {
            $message = 'Max post size exceeded! Got ' . $this->convertToReadableSize($_SERVER['CONTENT_LENGTH']) .
                ' bytes, but limit is ' . $this->convertToReadableSize($maxPostSize) . ' bytes.';

            // Throw the exception.
            throw new \Exception($message, 400);
        }

        // store the posted data.
        $data = $_POST;

        // Passing `true` enables exceptions.
        // Call method instead of using new so you can unit test this sendMail method.
        $mail = $this->createPHPMailer(true);
        try {
            // You need to make sure that your from address is a valid email account setup on that server.
            $mail->setFrom('no-reply@example.com', 'Server');

            // Recipients to.
            // Remove array the key that has empty value - recursively.
            $recipients = $this->filterRecipients($emailsTo);
            if (count($recipients) > 0) {
                foreach ($recipients as $recipient) {
                    $mail->addAddress($recipient['email'], $recipient['name']); // Add a recipient
                }
            }

            // Recipients Cc.
            // Remove array the key that has empty value - recursively.
            $recipients = $this->filterRecipients($emailsCc);
            if (count($recipients) > 0) {
                foreach ($recipients as $recipient) {
                    $mail->addCC($recipient['email'], $recipient['name']); // Add a recipient
                }
            }

            // Recipients Bcc.
            // Remove array the key that has empty value - recursively.
            $recipients = $this->filterRecipients($emailsBcc);
            if (count($recipients) > 0) {
                foreach ($recipients as $recipient) {
                    $mail->addCC($recipient['email'], $recipient['name']); // Add a recipient
                }
            }

            // Sender.
            $mail->addReplyTo($data['sender-email'], $data['sender-name']);

            // Attachments.
            $files = $this->uploadFiles();
            if (count($files) > 0) {
                foreach ($files as $file) {
                    if (move_uploaded_file($file['tmp_name'], $this->uploaddir . basename( $file['name']))) {
                        $this->uploaddir . $file['name'];
                    }

                    // Add attachments.
                    $mail->addAttachment($this->uploaddir . basename($file['name']));
                }
            }

            // Attache pdf.
            $pdfPath = $this->makePdf($data, $files);
            if (file_exists($pdfPath)) {
                // Add attachments.
                $mail->addAttachment($pdfPath);
            }

            // Content
            // Set email format to HTML
            $mail->isHTML(true);
            $mail->Subject = 'Quality control feedback from ' . $data['sender-name'];
            $mail->Body = ' ' .
                'Name: ' . $data['sender-name'] . '<br/>' .
                'Contact Email: ' . $data['sender-email'] . '<br/>' .
                'General Description of Problem/Concern/Fault: ' . $data['sender-description']
                ;

            $mail->send();

            // Remove the uploaded files.
            if (count($files) > 0) {
                foreach ($files as $file) {
                    $path = $this->uploaddir . basename($file['name']);
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }

            // Delete pdf.
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return 'Report has been sent.';

        } catch (Exception $e) {
            // Manual testing.
            // return 'Report has been sent.';

            // Throw the exception.
            throw new \Exception('Report could not be sent. Mailer Error: ' . $mail->ErrorInfo, 400);
        }
    }

    /**
     * Process of making a pdf.
     * @param  array $data
     * @param  array $files
     * @return string
     */
    private function makePdf(array $data, array $files)
    {
        // Make pdf.
        // Instantiate and use the dompdf class
        $dompdf = $this->createDompdf();

        // Create a DOM object from a HTML file.
        $filePath = 'view/pdf.php';
        if(file_exists($filePath)) {

            // Extract the variables to a local namespace
            extract($data);

            // Start output buffering
            ob_start();

            // Include the template file
            include $filePath;

            // End buffering and return its contents
            $html = ob_get_clean();
        }

        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Save the generated PDF document to disk instead of sending it to the browser.
        $output = $dompdf->output();
        $pdfOutput = 'Quality_Control_Feedback_' . date("YmdHis") . '.pdf';
        file_put_contents($this->uploaddir . $pdfOutput, $output);

        return $this->uploaddir . $pdfOutput;
    }

    /**
     * Process uploading files.
     * @return array or exception.
     */
    private function uploadFiles()
    {
        // Re-arrange the default file array.
        $files = [];
        if (isset($_FILES['sender-attachments']) && count($_FILES['sender-attachments']) > 0) {
            $files = $this->reArrayUploadFiles($_FILES['sender-attachments']);
        }

        // Validate the uploaded files.
        $validated = [];
        foreach ($files as $key => $file) {
            $validated[] = $this->validateUploadFiles($file, $this->maxsize, $this->whitelist);
        }

        // Scoop for any error.
        $error_upload = [];
        foreach ($validated as $key => $file) {
            if ($file ['error']) {
                if (isset($file ['name'])) {
                    $error_upload[] = $file ['name'] . ' - ' . $file ['error'] . ' ';
                } else {
                    $error_upload[] = $file ['error'] . ' ';
                }
            }
        }

        // Make sure no upload errors.
        if (count($error_upload) > 0) {
            throw new \Exception(implode('; ', $error_upload), 400);
        }
        return $files;
    }

    /**
     * Filter recipient data recursively.
     * @param  array  $recipients
     * @return array
     */
    private function filterRecipients(array $recipients)
    {
        // Remove array the key that has empty value - recursively.
        // https://stackoverflow.com/questions/7696548/php-how-to-remove-empty-entries-of-an-array-recursively
        return array_filter($recipients, [$this, "arrayFilter"]);
    }

    /**
     * Abstract the object so you can unit the sendMail method.
     * https://stackoverflow.com/questions/7760635/unit-test-for-mocking-a-method-called-by-new-class-object
     * @param  bool $boolean
     * @return object
     */
    private function createPHPMailer(bool $boolean)
    {
        return new PHPMailer($boolean);
    }

    /**
     * Abstract the object so you can unit the sendMail method.
     * https://stackoverflow.com/questions/7760635/unit-test-for-mocking-a-method-called-by-new-class-object
     * @param  bool $boolean
     * @return object
     */
    private function createDompdf()
    {
        return new Dompdf();
    }
}
