<?php
abstract class NotFoundHandler {
	abstract public function exception(AtPageNotFoundException $e, $app);
}
?>
