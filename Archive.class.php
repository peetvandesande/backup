<?php

class Archive {

    private $debug;
    private $meta;
    private $location;      // Directory where to store files
    private $filename;      //  Name (-ing basis) for files
    private $metadata_filename;
    private $file_extension;
    private $split_size;
    private $split_units;
    private $chunks;
    private $resources;
    private $exclusions;
    private $pgp_recipient;
    private $pgp_executable;
    private $compression_method;
    private $tar_options;
    private $tar_executable;
    private $archive_command;   // Actual command to create the archive
    private $split_executable;
    private $size;
    private $return;            // Return value

    public function __construct() {
        $this->debug = false;
        $this->meta = array(
            'version' => 'v2.0.0-alpha-2',
            'date' => '',
            'time_start' => 0,
            'time_stops' => 0,
            'location' => '',
            'filename' => '',
            'archive_command' => '',
            'size' => 0,
            'chunks' => array()
        );
        $this->location = ".";
        $this->filename = "";
        $this->metadata_filename = "";
        $this->file_extension = ".tar";
        $this->split_size = 0;      // Max chunk size
        $this->split_units = 'K';   // Units to measure split chunks
        $this->chunks = array();    // All the chunks created
        $this->resources = array(); // Files/directories to be added
        $this->exclusions = array();
        $this->pgp_recipient = "";
        $this->pgp_executable = "/usr/bin/pgp";
        $this->compression_method = "";
        $this->tar_options = array();
        $this->tar_executable = "/usr/bin/tar";
        $this->split_executable = "/usr/bin/split";
        $this->size = array();
        $this->return = false;
    }

    public function set_debug( $debug ) {
        $this->debug = $debug;
        $this->debug(sprintf("Setting debug to %s", $debug));
    }
    public function get_debug() {
        return $this->debug;
    }

    public function set_location( $location ) {
        $this->debug(sprintf("Current working directory is: %s", getcwd() ) );
        $this->location = $location;
        $this->debug(sprintf("Setting location to %s", $location));
        chdir( $location );
        $this->debug(sprintf("Current working directory is: %s", getcwd() ) );
    }
    public function get_location() {
        return $this->location;
    }

    public function set_filename($filename) {
        $this->filename = $filename;
        $this->debug(sprintf("Setting filename to %s", $filename));
        $this->assume_metadata_filename();
    }
    public function get_filename() {
        return $this->filename;
    }

    private function assume_metadata_filename() {
        if ($this->metadata_filename == "") {
            $this->set_metadata_filename(sprintf("%s.meta", $this->filename));
        }
    }

    public function set_metadata_filename($metadata_filename) {
        $this->metadata_filename = $metadata_filename;
        $this->debug(sprintf("Setting metadata_filename to %s", $metadata_filename));
    }
    public function get_metadata_filename() {
        return $this->metadata_filename;
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

    public function set_split_units($split_units) {
        $this->split_units = $split_units;
        $this->debug(sprintf("Setting split_units to %s", $split_units));
    }
    public function get_split_units() {
        return $this->split_units;
    }

    private function add_chunk($chunk) {
        $this->chunks[] = $chunk;
        $this->debug(sprintf("Adding chunk: %s", json_encode($chunk, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
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

    private function set_archive_command($command) {
        $this->archive_command = $command;
        $this->debug(sprintf("Setting archive_command to %s", $command));
    }

    public function get_archive_command() {
        return $this->archive_command;
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

    private function set_size($size) {
        $this->size = $size;
        $this->debug(sprintf('Size is: %d', $size));
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
        $this->meta['filename'] = $this->filename;
    }

    private function compose_archive_command() {
        /**
         * Create the tar command
         */
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

        $cmd = sprintf("%s %s -f %s%s%s",
            $this->tar_executable,
            $options,
            ($this->split_size > 0) ? "-" : $this->filename,
            $exclusions,
            $resources
        );
        $this->debug(sprintf("\$cmd: %s", $cmd));

        if ($this->split_size > 0) {
            $split = sprintf(" | %s -d -b %d%s - %s.",
                $this->split_executable,
                $this->split_size,
                $this->split_units,
                $this->filename
            );
            $this->debug(sprintf("\$split: %s", $split));
            $cmd .= $split;
        }

        $this->debug(sprintf("\$cmd: %s", $cmd));
        $this->archive_command = $cmd;
        $this->meta['archive_command'] = $this->archive_command;
    }

    private function list_chunks() {
        /**
         * List all chunks newly created
         */
        foreach (glob(sprintf("%s*", $this->filename)) as $file) {
            if (filectime($file) >= $this->meta['time_start']) {
                $this->debug(sprintf("Adding chunk with name: %s", $file));
                $chunk['filename'] = $file;
                $chunk['size'] = filesize($file);
                $this->add_chunk($chunk);
            } else {
                $this->debug(sprintf("Not adding file %s", $file));
            }
        }
    }

    private function calculate_size() {
        /**
         * Calculate size of all chunks
         */
        $total = 0;
        foreach ($this->chunks as $chunk) {
            $total += $chunk['size'];
        }
        $this->debug(sprintf('Total size of archive: %d', $total));
        $this->set_size($total);
        $this->meta['size'] = $this->size;
    }

    private function calculate_checksums() {
        /**
         * Calculates checksum for all chunks
         */
        $cursor = 0;
        foreach ($this->chunks as $chunk) {
            $md5sum = md5_file($chunk['filename']);
            $this->debug(sprintf("md5sum for %s is %s", $chunk['filename'], $md5sum));
            $this->chunks[$cursor]['md5sum'] = $md5sum;
            $cursor++;
        }
    }

    public function write_archive() {
        /**
         * Orchestrates writing the archive to file 
         */
        $this->meta['date'] = date('Ymd');
        $this->meta['time_start'] = time();
        $this->meta['time_stops'] = time();   // Placeholder to keep both values together
        $this->check_filename();
        $this->add_tar_option('c');
        $this->compose_archive_command();

        $output = "";
        $retval = "";
        $this->debug(sprintf("Current working directory is: %s", getcwd() ) );
        if ($this->shell($this->archive_command, $output, $retval)) {
			$this->return = true;
            $this->debug(sprintf("Archive %s written at location %s", $this->filename, $this->location));
		} else {
            exit("Unable to write archive\n");
        }

        $this->list_chunks();
        $this->calculate_size();
        $this->calculate_checksums();
        $this->meta['chunks'] = $this->chunks;
        $this->write_meta_file();

        return $this->return;
    }

    private function write_meta_file() {
        /**
         * Write the meta array to a json file
         */
        $this->meta['time_stops'] = time();
        $metafile = fopen($this->metadata_filename,'w');
        if ($metafile) {
            fwrite($metafile, json_encode($this->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fclose($metafile);
        } else {
            $this->return = false;
        }
    }

    public function load_meta_file() {
        /**
         * Load existing meta file
         */
        $metafile = fopen($this->metadata_filename,'r');
        if ($metafile) {
            $this->meta[] = json_decode(fread($metafile, filesize($this->metadata_filename)), true);
            fread($metafile, json_encode($this->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fclose($metafile);
        } else {
            return(false);
        }

        $this->set_location($this->meta['location']);
        $this->set_filename($this->meta['filename']);
        $this->set_archive_command($this->meta['archive_command']);
        $this->set_size($this->meta['size']);

        foreach($this->meta['chunks'] as $chunk) {
            $this->add_chunk($chunk);
        }
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