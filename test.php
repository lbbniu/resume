<?php
class A{
	public static function  sho(){
		echo __CLASS__." ".get_called_class()."\n";
	}
	public static function  test(){
		self::sho();
		static::sho();
	}
}

class B extends A{
	public static function  sho(){
		echo __CLASS__." ".get_called_class()."\n";
	}
}
//B::test();
class Foo
{
    public $var = '3.1415962654';
}

for ( $i = 0; $i <= 1000000; $i++ )
{
    $a = new Foo;
    $a->self = $a;
}

echo memory_get_peak_usage(), "\n";