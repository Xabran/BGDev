<?php
/*
 * A cross-platform inter-process mutex. Designed specifically for use in
 * preventing race condition attacks:
 * https://defuse.ca/race-conditions-in-web-applications.htm
 *
 * This code is explcitly placed into the public domain by Defuse Cyber-Security.
 * You are free to use it for any purpose whatsoever.
 *
 * Always test your implementation to make sure the attack is being prevented!
 * If you have multiple servers processing requests, a simple mutex like this
 * will NOT prevent race condition attacks.
 *
 * Modifications made by VanboDevelops | Ivan Andreev
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// If we don't have the mutex-files directory, attempt to create it
if ( ! file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mutex-files' ) ) {
	$create = wp_mkdir_p( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mutex-files' );
}

define("SEM_SALT", "833311dba1970c8702e8be8d1762787e");
// Where to keep the lock files if we don't have the System V semaphore functions.
define("SEM_DIR", dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mutex-files'. DIRECTORY_SEPARATOR);
define("HAVE_SYSV", function_exists('sem_get'));

class TG_Mutex {
	private $semaphore;
	private $locked;

	/*
	 * $key - The mutex identifier.
	 * $key can be anything that reliably casts to a string.
	 */
	function __construct($key) {
		// Paranoia says: Do not let the client specify the actual key.
		$key = hexdec(substr(sha1(SEM_SALT . $key, false), 0, PHP_INT_SIZE * 2 - 1));
		$this->locked = FALSE;

		if ( HAVE_SYSV ) {
			$this->semaphore = sem_get($key, 1);
		} else {
			$lockfile = SEM_DIR . "{$key}.sem";
			$this->semaphore = fopen($lockfile, 'w+');
		}
	}

	/*
	 * Locks the mutex. If another thread/process has a lock on the mutex,
	 * this call will block until it is unlocked.
	 */
	function lock() {
		if ( $this->locked ) {
		    throw new Exception('Mutex is already locked');
		}

		if ( HAVE_SYSV ) {
		    $res = sem_acquire($this->semaphore);
		} else {
		    $res = flock($this->semaphore, LOCK_EX);
		}

		if ( $res ) {
		    $this->locked = TRUE;
		    return TRUE;
		} else {
			return FALSE;
		}
	}

	/*
	 * Unlocks the mutex.
	 */
	function unlock() {
		if ( ! $this->locked ) {
		    throw new Exception('Mutex is not locked');
		}

		if ( HAVE_SYSV ) {
			$res = sem_release($this->semaphore);
		} else {
			$res = flock($this->semaphore, LOCK_UN);
		}

		if ( $res ) {
			$this->locked = FALSE;
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/*
	 * Removes the mutex from the system.
	 */
	function remove() {
		if ( $this->locked ) {
			throw new Exception('Trying to delete a locked mutex');
		}

		if ( HAVE_SYSV )
			sem_remove($this->semaphore);
		else
			unlink($this->semaphore);
	}

	function __destruct() {
		if(!HAVE_SYSV) {
			if($this->locked) {
				// We are at the end of the process.
				// If the process still kept the lock, unlock it.
				$this->unlock();
			}

			fclose($this->semaphore);
		}
	}

}