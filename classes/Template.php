<?php
/**
 * A very basic template class.  It simplies render PHTML and returns the output
 */

class Template {
	/**
	 * The path to the templates folder
	 * @var string
	 */
	protected $base = '';

	/**
	 * If you build it they will render
	 * @param string $base The path to the templates folder
	 */
	public function __construct($base = '') {
		$this->base = $base;
	}

	/**
	 * Render a phtml template with the provided data
	 * @param  sting $template Path to the specific template
	 * @param  array  $data    Array of data in $key => $value pairs
	 * @return string          The rendered content
	 */
	public function render($template, $data = array()) {

		ob_start();
        
		extract($data);

        require ($this->base . '/' . $template . ".phtml");
        
        $content = ob_get_clean();
        
        ob_end_flush();

        return $content;
	}
}
