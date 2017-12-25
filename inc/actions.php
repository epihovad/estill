<?
@session_start();

require($_SERVER['DOCUMENT_ROOT'].'/inc/db.php');
require($_SERVER['DOCUMENT_ROOT'].'/inc/utils.php');
require($_SERVER['DOCUMENT_ROOT'].'/inc/spec.php');

if(isset($_GET['action']))
{
	switch($_GET['action'])
	{
		// ------------------- Форма обратной связи
		case 'feedback':

			$type = $_GET['type'] == 'cns' ? 'Консультация' : 'Сообщение';

			foreach($_POST as $k=>$v)
				$$k = clean($v);

			if($hdn) exit; // спам-боты

			if(!$name) jAlert('Пожалуйста, представьтесь');
			if($type == 'Консультация'){
				$phone = substr(preg_replace("/\D/",'',$phone), -10);
				if(strlen($phone) != 10) jAlert('Некорректный номер телефона');
      } else {
				if(!check_mail($email)) jAlert('Введен некорректный E-mail');
      }
			if(!$text) jAlert('Пожалуйста, введите Ваше сообщение');

			$mailto = array();

			ob_start();
			?>
      <b>Имя</b>: <?=$name?><br />
      <b>E-mail</b>: <?=$email?><br />
      <b>Телефон</b>: <?=$phone?'+7'.$phone:'-'?><br />
      <b>Сообщение</b>: <?=$text?><br />
			<?
			$mailto['text'] = ob_get_clean();

			$set = "type='{$type}', name='{$name}', email='{$email}', phone='{$phone}', text='{$text}'";

			if(!update('msg', $set)){
				$alert = 'Во время сохранения данных произошла ошибка.<br>Администрация сайта приносит Вам свои извинения.<br>Мы уже знаем об этой проблеме и работаем над её устранением.';
				$mailto['theme'] = 'Ошибка (Сообщение)';
			}
			else
			{
				$alert = 'Спасибо за Ваше обращение.<br>Сообщение уже отправлено нашему менеджеру';
				$mailto['theme'] = 'Сообщение';
			}

			// мылим админу
			mailTo(set('admin_mail'), $mailto['theme'], $mailto['text'], set('admin_mail'));

			?><script>top.jQuery(document).jAlert('show','alert','<?=cleanJS($alert)?>',function(){top.jQuery.arcticmodal('close')});</script><?
			break;

		// ------------------- Подписка на рассылку
		case 'subscribe':
			foreach($_REQUEST as $k=>$v)
				$$k = clean($v);

			if($hdn) exit; // спам-боты

			if(!check_mail($email)) jAlert('Введен некорректный E-mail');

			// проверка подписан ли уже email
			if($subs = getRow("SELECT * FROM {$prx}subscribers WHERE email = '{$email}'")){

				if(!$subs['unsubscribe_date']){
					$alert = 'Вы уже подписаны на нашу рассылку';
				} else {
					if(!update('subscribers',"unsubscribe_date = NULL", $subs['id'])){
						$alert = 'Во время сохранения данных произошла ошибка.<br>Администрация сайта приносит Вам свои извинения.<br>Мы уже знаем об этой проблеме и работаем над её устранением.';
					} else {
						$alert = 'Вы успешно подписаны на рассылку.<br>Благодарим за проявленный интерес.<br>Вы не пожалеете!';
					}
				}
			} else {

				if(!update('subscribers',"email = '{$email}'")){
					$alert = 'Во время сохранения данных произошла ошибка.<br>Администрация сайта приносит Вам свои извинения.<br>Мы уже знаем об этой проблеме и работаем над её устранением.';
				} else {
					$alert = 'Вы успешно подписаны на рассылку.<br>Благодарим за проявленный интерес.<br>Вы не пожалеете!';
				}
			}

			// мылим админу
			mailTo(set('admin_mail'), 'Подписка', 'У нас новый подписчик:<br>'.$email, set('admin_mail'));

			?>
      <script>
        top.jQuery('.fnews .btn').removeClass('disabled');
        top.jQuery(document).jAlert('show','alert','<?=cleanJS($alert)?>',function(){top.jQuery('.fnews input').val('')});
      </script>
      <?
			break;
	}
	exit;
}

if(isset($_GET['show']))
{
	switch($_GET['show'])
  {
		// ------------------- Форма обратной связи
		case 'feedback':
			$type = $_GET['type'];
			$h3 = '';
		  switch ($type){
        case 'phone': $h3 = 'Позвоните нам по бесплатному телефону <span>'.set('phone').'</span><br>или просто оставьте сообщение'; break;
				case 'email': $h3 = 'Напишите нам на E-mail:<br><span>'.nl2br(set('email')).'</span><br>или просто оставьте сообщение'; break;
        case 'cns':   $h3 = 'Закажите бесплатную консультацию'; break;
        default:      $h3 = 'Оставьте нам сообщение'; break;
      }
		  ?>
			<style>
				#frm-fb {text-align:center; width:385px;}
				#frm-fb h3 {margin: 0px 0px 20px;}
        #frm-fb h3 span { color:#313132; display:inline-block; padding-bottom:15px;}
				#frm-fb .form-control { width:100%;}
				#frm-fb textarea { resize:none; }
      </style>

			<form id="frm-fb" action="/inc/actions.php?action=feedback&type=<?=$type?>" class="frm" target="ajax" method="post">
				<h3><?=$h3?></h3>
				<div class="input-group">
					<span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
					<input class="form-control" placeholder="Как к Вам обращаться?" name="name" type="text">
				</div>
				<div class="clear" style="padding-bottom:10px;"></div>
        <? if($type == 'cns'){?>
        <div class="input-group">
          <span class="input-group-addon"><span class="glyphicon glyphicon-phone-alt"></span></span>
          <input class="form-control" placeholder="+7 (___) ___-__-__" name="phone" type="text">
        </div>
        <div class="clear" style="padding-bottom:10px;"></div>
        <?} else {?>
				<div class="input-group">
					<span class="input-group-addon">@</span>
					<input class="form-control" placeholder="Введите Ваш E-mail адрес" name="email" type="text">
				</div>
				<div class="clear" style="padding-bottom:10px;"></div>
        <?}?>
				<div class="input-group">
					<span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
					<textarea class="form-control" placeholder="Введите Ваше сообщение" rows="4" name="text"></textarea>
				</div>
				<div class="clear" style="padding-top:15px;"></div>
				<div class="btn btn-mini" onclick="jQuery('#frm-fb').submit();"><div>Отправить</div></div>
        <div class="hdn"><input type="text" name="hdn" value=""></div>
			</form>
			<?if($type == 'cns'){?>
      <script src="/js/jquery/inputmask.min.js"></script>
      <script src="/js/jquery/inputmask.phone.extensions.min.js"></script>
      <script>
        jQuery(document).ready(function( $ ) {
          Inputmask({mask: '+7 (999) 999-99-99',showMaskOnHover: false}).mask($('#frm-fb input[name="phone"]'));
        });
      </script>
      <?}
			break;
	}
	exit;
}