<?php
namespace de\anigu\opcachemofile;

defined( 'ABSPATH' ) or die();

class ArrayMappedEntries implements \ArrayAccess {
	private $backedBy;

	public function __construct(CachingMoFile $backedBy) {
		$this->backedBy = $backedBy;
	}

	public function offsetExists($offset) {
		return $this->backedBy->translationExists($offset);
	}

	public function offsetGet($offset) {
		return $this->backedBy->translationGet($offset);
	}

	public function offsetSet($offset, $value) {
		throw new RuntimeException("Cannot set translation for " . $offset);
	}

	public function offsetUnset($offset) {
		throw new RuntimeException("Cannot set translation for " . $offset);
	}
}

class CachingMoFile extends \Gettext_Translations {
	private $domain;
	private $mofile;
	private $mo = null;
	private $entriesArrays;
	private $modified = false;

	public function __construct($domain, $mofile) {
		$this->domain = $domain;
		$this->mofile = $mofile;

		$cacheFile = $this->getCacheFile();
		$cache = null;
		if (file_exists($cacheFile)) {
			try {
				$cache = include ($cacheFile);
				if (is_file($mofile) && (!preg_match('/^\\d+$/', $cache['mtime'] ?? '') || $cache ['mtime'] + 0 < filemtime($mofile))) {
					// cache is old
					// "Out of date: ${cache ['mtime']} < " + filemtime($mofile);
					$cache = null;
				}
			} catch ( \Throwable $e ) {
				// file corrupt => ignore
			}
		}

		if ($cache == null) {
			$trans = $this->getRealFile();
			$this->markForStore();
				
			$cache = [
					'entriesArrays' => array_map([
							$this,
							'transToArray'
					], $trans->entries),
					'headers' => $trans->headers
			];
		}

		// print_r($cache);

		$this->entries = new ArrayMappedEntries($this);
		$this->headers = $cache ['headers'];
		$this->entriesArrays = $cache ['entriesArrays'];
	}

	private function loadTranslation($offset) {
		if (!isset($this->entriesArrays [$offset])) {
			$mo = $this->getRealFile();
			if ($mo->entries [$offset] ?? null) {
				$entry = $this->transToArray($mo->entries [$offset]);
			} else {
				$entry = [];
			}
			$this->entriesArrays [$offset] = $entry;
			$this->markForStore();
		}
	}

	private function markForStore() {
		if (!$this->modified) {
			add_action('shutdown', [
					$this,
					'storeCache'
			]);
			$this->modified = true;
		}
	}

	private $foundQueries = [];

	function translate($singular, $context=null) {
		$key = CachingMoFile::moKey($singular, $context);
		if (!isset($this->entriesArrays[$key])) {
			// cache miss: Go to parent, let cache be filled
			return parent::translate($singular, $context);
		} else {
			$translated = $this->entriesArrays[$key];
			return $translated['translations'][0] ?? $singular;
		}
	}

	/**
	 * Copied from Translation_Entry
	 * @param unknown $singular
	 * @param unknown $context
	 * @return boolean|mixed
	 */
	private static function moKey($singular, $context) {
		if ( null === $singular || '' === $singular ) return false;

		// Prepend context and EOT, like in MO files
		$key = !$context? $singular : $context . chr(4) . $singular;
		// Standardize on \n line endings
		$key = str_replace( array( "\r\n", "\r" ), "\n", $key );

		return $key;
	}

	public function translationExists($offset) {
		$this->loadTranslation($offset);
		return isset($this->entriesArrays [$offset]);
	}

	public function translationGet($offset) {
		$this->loadTranslation($offset);
		if (isset($this->entriesArrays [$offset])) {
			return new \Translation_Entry($this->entriesArrays [$offset]);
		} else {
			return null;
		}
	}

	public function transToArray(\Translation_Entry $trans) {
		$res = (array) $trans;
		$empty = (array) new \Translation_Entry();
		foreach ($empty as $key => $defaultValue) {
			if (isset ($res[$key]) && $res[$key] == $defaultValue) {
				unset($res[$key]);
			}
		}
		return $res;
	}

	private function getCacheFile() {
		return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . md5($this->mofile) . '.php';
	}

	private function getRealFile() {
		if ($this->mo === null) {
			$mofile = $this->mofile;
			$this->mo = new \MO();
			if (file_exists($mofile)) {
				$this->mo->import_from_file($mofile);
			}
		}
		return $this->mo;
	}

	public function storeCache() {
		$contentsArray = [
				'mtime' => filemtime($this->mofile) . '',
				'entriesArrays' => $this->entriesArrays,
				'headers' => $this->headers
		];

		$contents = '<?php return ' . $this->phpArrayToSource($contentsArray) . ';';
		$file = $this->getCacheFile();
		if (!is_dir(dirname($file))) {
			mkdir(dirname($file));
		}
		file_put_contents($file, $contents);
	}

	private function phpArrayToSource(array $array) {
		$out = '[';
		foreach ( $array as $key => $value ) {
			$out .= $this->phpPrimitiveToSource($key);
			$out .= '=>';
			$out .= is_array($value) ? $this->phpArrayToSource($value) : $this->phpPrimitiveToSource($value);
			$out .= ',';
		}
		$out .= ']';
		return $out;
	}

	private function phpPrimitiveToSource($value) {
		if (is_string($value) || is_numeric($value) || $value === null || $value === true || $value === false) {
			return var_export($value, true);
		} else {
			return 'null';
		}
	}
}
class OpcacheMoFileManager {

	public function override_load_textdomain($override, $domain, $mofile) {
		global $l10n;
		if ($override) {
			return true;
		}

		if (!is_admin()) {
			$mofile = apply_filters('load_textdomain_mofile', $mofile, $domain);
			if ( !is_readable( $mofile ) ) return false;
			$l10n [$domain] = new CachingMoFile($domain, $mofile);
			return true;
		} else {
			// Do not cache admin sites -> They will blow up the cache a lot.
			return false;
		}
	}

	public function register() {
		add_filter('override_load_textdomain', [
				$this,
				'override_load_textdomain'
		], 1, 3);
	}
}

(new OpcacheMoFileManager())->register();
