<?php

class Archive {

    private $debug;
    private $filename;
    private $file_extension;
    private $split_size;
    private $chunks;
    private $resources;
    private $exclusions;
    private $pgp_recipient;
    private $pgp_executable;
    private $compression_method;
    private $tar_options;
    private $tar_executable;
    private $split_executable;
    private $md5sum;
    private $size;

    public function __construct() {
        $this->debug = false;
        $this->filename = "";
        $this->file_extension = ".tar";
        $this->split_size = 0;      // Max chunk size in MB
        $this->chunks = array();    // All the chunks created
        $this->resources = array();
        $this->exclusions = array();
        $this->pgp_recipient = "";
        $this->pgp_executable = "/usr/bin/pgp";
        $this->compression_method = "bzip2";
        $this->tar_options = array();
        $this->tar_options[] = "c";    // Create new archive
        $this->tar_options[] = "p";    // Preserve permissions
        $this->tar_options[] = "P";    // Absolute names
        $this->tar_executable = "/usr/bin/tar";
        $this->split_executable = "/usr/bin/split";
        $this->md5sum = array();
        $this->size = array();
    }

    public function set_debug( $debug ) {
        $this->debug = $debug;
        $this->debug(sprintf("Setting debug to %s", $debug));
    }
    public function get_debug() {
        return $this->debug;
    }

    public function set_filename($filename) {
        $this->filename = $filename;
        $this->debug(sprintf("Setting filename to %s", $filename));
    }
    public function get_filename() {
        return $this->filename;
    }

    public function set_file_extension($extension) {
        $this->file_extension = $extension;
        $this->debug(sprintf("Setting file_extension to %s", $extension));
    }
    public function get_file_extension() {
        return $this->file_extension;
    }

    public function set_split_size($split_size) {
        $this->split_size = $split_size;
        $this->debug(sprintf("Setting split_size to %s", $split_size));
    }
    public function get_split_size() {
        return $this->split_size;
    }

    private function set_chunks() {
        foreach (glob(sprintf("%s*", $this->filename)) as $file) {
            $this->chunks[] = $file;
            $this->debug(sprintf("Adding %s to chunks", $file));
        }
    }
    public function get_chunks() {
        return $this->chunks;
    }

    public function add_resource($resource) {
        if(!in_array($resource, $this->resources)) {
            $this->resources[] = $resource;
            $this->debug(sprintf("Adding %s to resources", $resource));
        }
    }
    public function get_resources() {
        return $this->resources;
    }

    public function add_exclusion($exclusion) {
        if(!in_array($exclusion, $this->exclusions)) {
            $this->exclusions[] = $exclusion;
            $this->debug(sprintf("Adding %s to exclusions", $exclusion));
        }
    }
    public function get_exclusions() {
        return $this->exclusions;
    }

    public function set_pgp_recipient($pgp_recipient) {
        $this->pgp_recipient = $pgp_recipient;
        $this->debug(sprintf("Setting pgp_recipient to %s", $pgp_recipient));
    }
    public function get_pgp_recipient() {
        return $this->pgp_recipient;
    }

    public function set_pgp_executable($pgp_executable) {
        $this->pgp_executable = $pgp_executable;
        $this->debug(sprintf("Setting pgp_executable to %s", $pgp_executable));
    }
    public function get_pgp_executable() {
        return $this->pgp_executable;
    }

    public function set_compression_method($compression_method) {
        $this->compression_method = $compression_method;
        $this->debug(sprintf("Setting compression_method to %s", $compression_method));
    }
    public function get_compression_method() {
        return $this->compression_method;
    }

    public function add_tar_option($tar_option) {
        if (!in_array($tar_option, $this->tar_options)) {
            $this->tar_options[] = $tar_option;
            $this->debug(sprintf("Adding %s to tar_options", $tar_option));
        }
    }
    public function get_tar_options() {
        return $this->tar_options;
    }

    public function set_tar_executable($tar_executable) {
        $this->tar_executable = $tar_executable;
        $this->debug(sprintf("Setting tar_executable to %s", $tar_executable));
    }
    public function get_tar_executable() {
        return $this->tar_executable;
    }

    public function set_split_executable($split_executable) {
        $this->split_executable = $split_executable;
        $this->debug(sprintf("Setting split_executable to %s", $split_executable));
    }
    public function get_split_executable() {
        return $this->split_executable;
    }

    private function set_md5sum() {
        /**
         * Calculates MD5SUM for all chunks
         */
        foreach ($this->chunks as $chunk) {
            $this->debug(sprintf("Calculating md5sum for %s", $chunk));
        }
    }
    public function get_md5sum() {
        $this->set_md5sum();
        return $this->md5sum;
    }

    public function get_size() {
        return $this->size;
    }

    private function check_filename() {
        /**
         * Verifies file naming
         */
        switch ($this->compression_method) {
            case "":
                break;
            case "bzip2":
                $this->add_tar_option("j");
                $this->set_file_extension(sprintf("%s.bz2", $this->file_extension));
                break;
            case "gzip":
                $this->add_tar_option("z");
                $this->set_file_extension(sprintf("%s.z", $this->file_extension));
                break;
        }

        if (mb_substr($this->filename, (mb_strlen($this->file_extension) * -1)) != $this->file_extension) {
            $this->set_filename(sprintf("%s%s",
                $this->filename,
                $this->file_extension
            ));
        }
    }

    public function write() {
        /**
         * Writes archive to file 
         */
        $this->check_filename();
        $exclusions = "";
        foreach ($this->exclusions as $exclusion) {
            $exclusions .= sprintf(" --exclude=%s", $exclusion);
        }

        $resources = "";
        foreach ($this->resources as $resource) {
            $resources .= sprintf(" %s", $resource);
            $this->debug(sprintf("\$resources is now '%s'", $resources));
        }

        $options = "-";
        foreach ($this->tar_options as $option) {
            $options .= $option;
            $this->debug(sprintf("\$options is now '%s'", $options));
        }

        $cmd = sprintf("%s %s -f %s %s %s",
            $this->tar_executable,
            $options,
            ($this->split_size > 0) ? "-" : $this->filename,
            $exclusions,
            $resources
        );
        $this->debug(sprintf("\$cmd: %s", $cmd));

        if ($this->split_size > 0) {
            $split = sprintf(" | %s -d -b %dM - %s.",
                $this->split_executable,
                $this->split_size,
                $this->filename
            );
            $cmd .= $split;
            $this->debug(sprintf("\$split: %s", $split));
        }

        $this->debug(sprintf("\$cmd: %s", $cmd));
        if ($this->shell($cmd, $output, $retval)) {
			$this->inform("Archive written");
		}

        $this->set_chunks();
    }

    private function get_metadata_filename() {
        $path_parts = pathinfo($this->filename);
        if ($this->split_size > 0) {
            preg_match('/(\w*)(-{.*})(.*)/', $path_parts['basename'], $matches);
        } else {
            preg_match('/(\w*)\.(.*)/', $path_parts['basename'], $matches);
        }
        $metada_filename = sprintf("%s/%s.meta",
            $path_parts['dirname'],
            $matches[1]
        );
        return $metada_filename;
    }

    public function pgp_encrypt() {
        /**
         * PGP Encrypts the archive
         */
        $encrypted_filename = sprintf("%s.gpg", $this->filename);
        $cmd = sprintf("%1$s --encrypt -r %2$s --output %3$s.gpg %4$s && rm %4$s",
            $this->pgp_executable,
            $this->pgp_recipient,
            $encrypted_filename,
		    $this->filename);
        $this->debug(sprintf("\$cmd: %s", $cmd));
        if ($this->shell($cmd, $output, $retval)) {
			$this->inform(sprintf("Archive encrypted for: %s", $this->pgp_recipient));
			$this->filename = $encrypted_filename;
		}
    }

	private function inform($msg, $newline = true)
	{
		$output = sprintf("%s%s", $msg, $newline ? "\n" : "");
		echo $output;
	}
	
	private function debug($msg)
	{
		($this->debug) ? $output = sprintf("DEBUG: %s | %s\n", date("H:i:s"), $msg) : $output = "";
		print $output;
	}
	
	private function shell($cmd, &$stdout, &$stderr)
	{
		$descriptorspec = [
			0 => ["pipe", "r"],  // stdin
			1 => ["pipe", "w"],  // stdout
			2 => ["pipe", "w"],  // stderr
		 ];
	
		 $process = proc_open($cmd, $descriptorspec, $pipes);
	
	     if ($process === false) {
		    $stderr = stream_get_contents(($pipes[2]));
	        $this->inform("Error:");
	        foreach ($stderr as $line) {
	            $this->inform("$line");
	        }
	        return false;
	    }
	
		 $stdout = stream_get_contents(($pipes[1]));
		 fclose($pipes[1]);
		 fclose($pipes[2]);
	
		 proc_close($process);
	    return true;
	}
}