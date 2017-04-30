<?php
	//生成js
date_default_timezone_set('PRC');
	$pdo = new PDO('mysql:dbname=db_shangtuan;host=127.0.0.1;charset=utf8;','root','zkeys');
	$result = $pdo->query('select id,title,sendtime,img1 from dy_news where img1<>"" order by sendtime desc,id desc limit 20');
	$a = [];
	foreach ($result as $v) {
    $v['img1'] = 'file:///F:/projects/up/uploadfile/upload/'.$v['img1'];
		$v['date'] = date('Y-m-d H:i:s',$v['sendtime']);
    $v['sendtime'] *= 1000;
		$a[] = $v;
	}
	$json = json_encode($a);
	ob_start();
	ECHO "var app = angular.module('myApp',[]);
      app.controller('myCtrl',function(\$scope,\$rootScope){
      //\$scope.names = $json,
			\$rootScope.names = $json,
			\$scope.hello=function(){
				\$scope.greeting = 'hello whm and ' + \$scope.name
      }
      });
      app.filter('add',function(){
        return function(val){
          return val-100;
        }
	})";
	$content = ob_get_contents();
	$js = jsMin::minify($content);
  file_put_contents('angular.app.js', $content);
	file_put_contents('angular.app.min.js', $js);
?>

<?php
/**
 * @package JSMin
 * [demo]
 * $content = ob_get_contents();
 * $js = jsMin::minify($content);
 * file_put_contents('angular.app.js', $js);
 */
class JSMin {
  const ORD_LF    = 10;
  const ORD_SPACE = 32;

  protected $a           = '';
  protected $b           = '';
  protected $input       = '';
  protected $inputIndex  = 0;
  protected $inputLength = 0;
  protected $lookAhead   = null;
  protected $output      = '';

  // -- Public Static Methods --------------------------------------------------

  public static function minify($js) {
    $jsmin = new JSMin($js);
    return $jsmin->min();
  }

  // -- Public Instance Methods ------------------------------------------------

  public function __construct($input='') {
    $this->input       = str_replace("\r\n", "\n", $input);
    $this->inputLength = strlen($this->input);
  }

  // -- Protected Instance Methods ---------------------------------------------

  protected function action($d) {
    switch($d) {
      case 1:
        $this->output .= $this->a;

      case 2:
        $this->a = $this->b;

        if ($this->a === "'" || $this->a === '"') {
          for (;;) {
            $this->output .= $this->a;
            $this->a       = $this->get();

            if ($this->a === $this->b) {
              break;
            }

            if (ord($this->a) <= self::ORD_LF) {
              throw new JSMinException('Unterminated string literal.');
            }

            if ($this->a === '\\') {
              $this->output .= $this->a;
              $this->a       = $this->get();
            }
          }
        }

      case 3:
        $this->b = $this->next();

        if ($this->b === '/' && (
            $this->a === '(' || $this->a === ',' || $this->a === '=' ||
            $this->a === ':' || $this->a === '[' || $this->a === '!' ||
            $this->a === '&' || $this->a === '|' || $this->a === '?')) {

          $this->output .= $this->a . $this->b;

          for (;;) {
            $this->a = $this->get();

            if ($this->a === '/') {
              break;
            } elseif ($this->a === '\\') {
              $this->output .= $this->a;
              $this->a       = $this->get();
            } elseif (ord($this->a) <= self::ORD_LF) {
              throw new JSMinException('Unterminated regular expression '.
                  'literal.');
            }

            $this->output .= $this->a;
          }

          $this->b = $this->next();
        }
    }
  }

  protected function get() {
    $c = $this->lookAhead;
    $this->lookAhead = null;

    if ($c === null) {
      if ($this->inputIndex < $this->inputLength) {
        $c = $this->input[$this->inputIndex];
        $this->inputIndex += 1;
      } else {
        $c = null;
      }
    }

    if ($c === "\r") {
      return "\n";
    }

    if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
      return $c;
    }

    return ' ';
  }

  protected function isAlphaNum($c) {
    return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
  }

  protected function min() {
    $this->a = "\n";
    $this->action(3);

    while ($this->a !== null) {
      switch ($this->a) {
        case ' ':
          if ($this->isAlphaNum($this->b)) {
            $this->action(1);
          } else {
            $this->action(2);
          }
          break;

        case "\n":
          switch ($this->b) {
            case '{':
            case '[':
            case '(':
            case '+':
            case '-':
              $this->action(1);
              break;

            case ' ':
              $this->action(3);
              break;

            default:
              if ($this->isAlphaNum($this->b)) {
                $this->action(1);
              }
              else {
                $this->action(2);
              }
          }
          break;

        default:
          switch ($this->b) {
            case ' ':
              if ($this->isAlphaNum($this->a)) {
                $this->action(1);
                break;
              }

              $this->action(3);
              break;

            case "\n":
              switch ($this->a) {
                case '}':
                case ']':
                case ')':
                case '+':
                case '-':
                case '"':
                case "'":
                  $this->action(1);
                  break;

                default:
                  if ($this->isAlphaNum($this->a)) {
                    $this->action(1);
                  }
                  else {
                    $this->action(3);
                  }
              }
              break;

            default:
              $this->action(1);
              break;
          }
      }
    }

    return $this->output;
  }

  protected function next() {
    $c = $this->get();

    if ($c === '/') {
      switch($this->peek()) {
        case '/':
          for (;;) {
            $c = $this->get();

            if (ord($c) <= self::ORD_LF) {
              return $c;
            }
          }

        case '*':
          $this->get();

          for (;;) {
            switch($this->get()) {
              case '*':
                if ($this->peek() === '/') {
                  $this->get();
                  return ' ';
                }
                break;

              case null:
                throw new JSMinException('Unterminated comment.');
            }
          }

        default:
          return $c;
      }
    }

    return $c;
  }

  protected function peek() {
    $this->lookAhead = $this->get();
    return $this->lookAhead;
  }
}

// -- Exceptions ---------------------------------------------------------------
class JSMinException {}
//class JSMinException extends Exception {}
?>