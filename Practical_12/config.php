<?php
// Practical 12 : Central config - poster upload rules + shared upload helper
// File validation: type (extension + MIME + real image check) and size.

define("UPLOAD_DIR", __DIR__ . "/uploads/");
define("UPLOAD_URL", "uploads/");
define("MAX_POSTER_SIZE", 2 * 1024 * 1024); // 2 MB

$ALLOWED_EXTS  = ["jpg", "jpeg", "png", "gif", "webp"];
$ALLOWED_MIMES = ["image/jpeg", "image/png", "image/gif", "image/webp"];

/**
 * Validate + move an uploaded poster.
 *
 * @param array  $file    $_FILES['poster'] entry
 * @param string &$error  filled with "Failure: ..." message on failure
 * @return string|null  stored file name on success, NULL if no file uploaded,
 *                      FALSE on validation/move failure
 */
function handlePosterUpload($file, &$error) {
    global $ALLOWED_EXTS, $ALLOWED_MIMES;

    // No file chosen (only acceptable on edit page)
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // 1. PHP upload error codes
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE   => "file exceeds server limit (php.ini).",
            UPLOAD_ERR_FORM_SIZE  => "file exceeds form MAX_FILE_SIZE.",
            UPLOAD_ERR_PARTIAL    => "file was only partially uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "missing temporary folder on server.",
            UPLOAD_ERR_CANT_WRITE => "failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "a PHP extension blocked the upload.",
        ];
        $detail = $map[$file["error"]] ?? ("unknown upload error (" . $file["error"] . ").");
        $error = "Failure: Poster upload failed - " . $detail;
        return false;
    }

    // 2. Size validation (2 MB max)
    if ($file["size"] <= 0) {
        $error = "Failure: Uploaded poster is empty.";
        return false;
    }
    if ($file["size"] > MAX_POSTER_SIZE) {
        $error = "Failure: Poster must be 2 MB or smaller (uploaded "
               . round($file["size"] / 1024) . " KB).";
        return false;
    }

    // 3. Extension validation
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXTS, true)) {
        $error = "Failure: Invalid poster type. Allowed: JPG, JPEG, PNG, GIF, WEBP.";
        return false;
    }

    // 4. MIME validation (do not trust $_FILES['type'])
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file["tmp_name"]) : "";
    if ($finfo) { finfo_close($finfo); }
    if (!in_array($mime, $ALLOWED_MIMES, true)) {
        $error = "Failure: Invalid poster content (detected: " . htmlspecialchars($mime) . "). Only image files allowed.";
        return false;
    }

    // 5. Real-image check (rejects renamed .php / scripts)
    if (@getimagesize($file["tmp_name"]) === false) {
        $error = "Failure: Uploaded file is not a valid image.";
        return false;
    }

    // 6. Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            $error = "Failure: Could not create uploads directory.";
            return false;
        }
    }

    // 7. Unique, safe file name (prevents overwrite + path traversal)
    $newName = "event_" . date("Ymd_His") . "_" . bin2hex(random_bytes(6)) . "." . $ext;
    $dest    = UPLOAD_DIR . $newName;

    if (!is_uploaded_file($file["tmp_name"])) {
        $error = "Failure: Invalid upload source.";
        return false;
    }
    if (!move_uploaded_file($file["tmp_name"], $dest)) {
        $error = "Failure: Could not save poster on server.";
        return false;
    }

    return $newName;
}

/** Delete a stored poster file (used on update-replace and delete). */
function deletePosterFile($fileName) {
    if ($fileName === null || $fileName === "") return;
    // basename() guard against path traversal values stored in DB
    $path = UPLOAD_DIR . basename($fileName);
    if (is_file($path)) { @unlink($path); }
}
?>
