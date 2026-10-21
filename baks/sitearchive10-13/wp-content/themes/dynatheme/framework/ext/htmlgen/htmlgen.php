<?php

/**
 * Class Utility
 */
class Utility {
  
  /**
   * php doesn't have this function; ftfy
   *
   * @param callable $callback
   * @param array $input
   * @return array
   */
  static public function array_kmap(Closure $callback, Array $input){
    $output = array();
    foreach($input as $key => $value){
      array_push($output, $callback($key, $value));
    }
    return $output;
  }
  
  #
  /**
   * reverse merge arrays
   *
   * @param array $overrides
   * @param array $options
   */
  static public function reverse_merge(Array &$overrides, Array $options){
    $overrides = array_merge($options, $overrides);
  }

  /**
   * captures the output of a Closure
   *
   * @param callable $call
   * @return string
   */
  static public function capture(Closure $call){
    ob_start(); $call(); return ob_get_clean();
  }
  
}

/**
 * Class HtmlGen
 */
class HtmlGen {

  /**
   * @var array
   */
  static public $self_closing_tags = array(
    "base", "basefont", "br", "col", "frame", "hr", "input", "link", "meta", "param"
  );

  /**
   * @var int
   */
  static private $indent_level = -1;

  /**
   * @var string
   */
  static private $indent_pattern = "\t";

  /**
   * @var array
   */
  static private $cycles = array();

  /**
   * @var array
   */
  static private $variables = array();

  /**
   * the magic
   *
   * @param string $tag
   * @param array $args
   */
  static public function __callStatic($tag, $args){
    $params = array(null, array(), null);
    foreach($args as $a){
      if    (is_string($a))         $params[0] = $a;
      elseif(is_array($a))          $params[1] = $a;
      elseif($a instanceof Closure) $params[2] = $a;
    }
    array_unshift($params, $tag);
    call_user_func_array(array("self", "content_for"), $params);
  }

  /**
   * tag generator
   *
   * @param string $tag
   * @param string $text
   * @param array $html_attributes
   * @param callable $callback
   */
  static private function content_for($tag, $text=null, $html_attributes=array(), $callback=null){
    
    # indent
    echo self::indent();
    
    # self-closing tag
    if(in_array($tag, self::$self_closing_tags)){
      echo "<{$tag}", self::attributes($html_attributes), " />\n";
    }
    
    # standard tag
    else {
      echo "<{$tag}", self::attributes($html_attributes), ">";
      
      # block
      if($callback instanceof Closure){
        try {
          echo "\n", Utility::capture($callback), self::indent(false);
        } catch (Exception $e) {
          echo "foobasket!";
        }
      }
      
      # single line
      else echo $text;
        
      echo "</{$tag}>\n";
    }
    
    # outdent
    self::outdent();
  }

  /**
   * html attribute generator
   *
   * @param array $attributes
   * @return string
   */
  static private function attributes(Array $attributes){
    if(count($attributes) < 1){
      return null;
    }
    return " ".implode(" ", Utility::array_kmap(function($k, $v){
      return "{$k}=\"".htmlspecialchars($v)."\"";
    }, $attributes));
  }

  /**
   * example convenience method override
   *
   * @param string $text
   * @param string $href
   * @param array $html_attributes
   */
  static public function a($text, $href, $html_attributes=array()){
    Utility::reverse_merge($html_attributes, array('href' => $href));
    self::__callStatic("a", array($text, $html_attributes));
  }

  /**
   * comment tag
   *
   * @param string $comment
   */
  static public function comment($comment){
    echo "\n", self::indent(), "<!-- {$comment} -->\n";
    self::outdent();
  }

  /**
   * set indent level
   *
   * @param int $level
   */
  static public function set_indent_level($level){
    if(is_numeric($level)) self::$indent_level = $level-1;
  }

  /**
   * set indent pattern
   *
   * @param string $pattern
   */
  static public function set_indent_pattern($pattern){
    self::$indent_pattern = $pattern;
  }

  /**
   * increase indent level
   *
   * @param bool $increment
   * @return string
   */
  static private function indent($increment=true){
    if($increment) self::$indent_level++;
    $tabs = "";
    for($i=0; $i<self::$indent_level; $i++) $tabs .= self::$indent_pattern;
    return $tabs;
  }

  /**
   * decrease indent level
   */
  static private function outdent(){
    self::$indent_level--;
  }

  /**
   * cycler
   *
   * @param array $options
   * @param string $handle
   * @return mixed
   */
  static public function cycle(Array $options, $handle="default"){
    if(!array_key_exists($handle, self::$cycles)){
      self::$cycles[$handle] = $options;
      return current(self::$cycles[$handle]);
    }
    else {
      if($ret = next(self::$cycles[$handle])){
        return $ret;
      }
      else {
        reset(self::$cycles[$handle]);
        return current(self::$cycles[$handle]);
      }
    }
  }

  /**
   * reset specific cycle
   *
   * @param string $handle
   */
  static public function reset_cycle($handle="default"){
    if(array_key_exists($handle, self::$cycles)){
      reset(self::$cycles[$handle]);
    }
  }

  /**
   * sets a variable
   *
   * @param string $name
   * @param mixed $value
   */
  static public function set_variable($name, $value) {
    static::$variables[$name] = $value;
  }

  /**
   * retrieves a variable by name
   *
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  static public function get_variable($name, $default = null) {
    if (isset(static::$variables[$name])) {
      return static::$variables[$name];
    } else {
      return $default;
    }
  }
  
}

# alias +h+ class
if(!class_exists("h")){ class_alias('HtmlGen', 'h'); };