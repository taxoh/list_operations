<?php

mb_internal_encoding('utf-8');

$res = $json = [];
switch ($_POST['action'])
{
	case 'mul':
		$f1 = preg_split('/[\r\n]+/', $_POST['f1']);
		$f2 = preg_split('/[\r\n]+/', $_POST['f2']);
		foreach ($f1 as $s1)
		{
			foreach ($f2 as $s2)
			{$res[] = $s1.' '.$s2;}
		}
		$json['res'] = implode("\n", $res);
		$json['info'] = [count($res)];
	break;
	case 'mux':
		$was_post = true;
		$p = str_replace("\r", '', $_POST['src']);
		foreach ($p as &$v)
		{
			$v = explode("\n", $v);
			$max = max($max, count($v));
		}
		unset($v);
		for ($i=0;$i<$max;$i++)
		{
			$z = [];
			foreach ($p as $v) $z[] = $v[$i];
			$res[] = implode($_POST['delim'], $z);
		}
		$json['res'] = implode("\n", $res);
		$json['info'] = [count($res)];
	break;
	case 'demux':
		$p = str_replace("\r", '', $_POST['src']);
		$p = explode("\n", $p);
		$res_list = [];
		$max = 0;
		foreach ($p as $pp)
		{
			$pp = explode($_POST['delim'], $pp);
			$max = max($max, count($pp));
		}
		foreach ($p as $pp)
		{
			$pp = explode($_POST['delim'], $pp);
			for ($i=0;$i<$max_fields;$i++)
			{$res_list[$i][] = $pp[$i];}
		}
		foreach ($res_list as &$v)
		{$v = implode("\n", $v);}
		unset($v);
		$json['res_list'] = $res_list;
		$json['info'] = [count($res_list)];
	break;
	case 'diff':
		$f1 = preg_split('/[\r\n]+/', $_POST['f1']);
		$f2 = preg_split('/[\r\n]+/', $_POST['f2']);
		$res = array_diff($f1,$f2);
		$json['res'] = implode("\n", $res);
		$json['info'] = [count($f1), count($f2), count($res)];
	break;
}
if ($_POST['action']!='')
{
	header('Content-Type: application/javascript');
	echo json_encode($json);
	die();
}

header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<title>List Operations</title>
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
	$(document).ready(function(){
		$(".submit").click(function(){
			var f = $(this).closest(".box");
			var f2 = f.find("form");
			$.post("?", f2.serialize()+"&action="+f.attr("id"), function(data){
				if (typeof data.res_list!="undefined")
				{
					f.find("#demux_main").remove();
					f.append('<table id=demux_main></table>');
					var f3 = f.find('#demux_main');
					for (i in data.res_list)
					{f3.append('<tr><td><textarea class="res" data-num="'+i+'" readonly=yes></textarea></td></tr>');}
					for (i in data.res_list)
					{f.find('.res[data-num='+i+']').val(data.res_list[i]);}
				}
					else
				{f.find(".res").val(data.res);}
				if (typeof data.info!="undefined")
				{
					var i = 0;
					f.find("span").each(function(){
						$(this).text(data.info[i++]);
					});
				}
			}, "json");
			return false;
		});
		$(".moar").click(function(){
			var t = $('#mux_main');
			t.append("<tr><td><textarea name='src[]'></textarea></td><td></td></tr>");
		});
		$(".del").click(function(){
			var t = $('#mux_main');
			if (t.find("tr").length>1)
			{t.find("tr:last").remove();}
		});
		$("textarea.res").click(function(){
			$(this).get(0).select();
		});
		// заполняется меню
		var s = [];
		$(".box").each(function(){
			s.push('<a href="#" data-id="'+$(this).attr("id")+'">'+$(this).attr("data-hint")+'</a>');
		});
		$("#menu").html(s.join(" | "));
		$("#menu a").click(function(){
			$(".box").hide();
			$("#menu a").removeAttr("style");
			$(this).css('font-weight', 'bold');
			var a = $("#"+$(this).attr("data-id"));
			a.show();
			return false;
		});
	});
</script>
<style>
	textarea {width: 800px; height:100px;}
	#mux textarea {width: 800px; height:50px;}
	#menu a {font-size:16px;}
	.box {display:none;}
	.submit {font-weight:bold;}
</style>
</head>
<body>

<div id=menu></div>

<div class="box" id="mul" data-hint="List Mul">
	<p>"Умножает" списки: к каждой строке из первого списка по очереди добавляются строки из второго (через пробел), тем самым создавая строки результата.</p>
	<form>
		Список #1:<br>
		<textarea name=f1></textarea> <br>
		Список #2:<br>
		<textarea name=f2></textarea> <br>
		<button class=submit>Запуск</button>
	</form>
	<p> Результат: <b><span>0</span></b> строк </p>
	<textarea class="res" readonly=yes></textarea>
</div>

<div class="box" id="mux" data-hint="List Mux">
	<form>
		<p> Мультиплексирует (склеивает) несколько списков в один, добавляя разделитель. Иными словами: на входе содержимое "колонок", на выходе "табличка".</p>
		<p>
			разделитель: <input type=text name=delim size=4 value="|">
			<button class=submit>Запуск</button>
		</p>
		<table id=mux_main>
			<tr>
				<td><textarea name="src[]"></textarea></td>
				<td>
					<?if($k==0):?>
						<input type=button class="moar" value="добавить колонку">
						<input type=button class="del" value="удалить">
					<?endif?>
				</td>
			</tr>
		</table>
		<p> Результат: <b><span>0</span></b> строк </p>
		<textarea class="res" readonly=yes></textarea>
	</form>
</div>

<div class="box" id="demux" data-hint="List Demux">
	<form>
		<p> Демультиплексирует (раскладывает) список на несколько списков. Иными словами: на входе "табличка", на выходе содержимое "колонок".</p>
		<p>
			разделитель: <input type=text name=delim size=4 value="|">
			<button class=submit>Запуск</button>
		</p>
		<textarea name="src"></textarea><br>
		<p> Результат: <b><span>0</span></b> колонок </p>
	</form>
</div>

<div class="box" id="diff" data-hint="List Diff">
	<p>Находит разницу между двумя списками, выводит результат на экран.</p>
	<form>
		Откуда вычесть: <br>
		<textarea name=f1></textarea> <br>
		Что вычесть: <br>
		<textarea name=f2></textarea> <br>
		<button class=submit>Запуск</button>
	</form>
	<p> Результат: <b><span>0</span> - <span>0</span> = <span>0</span></b> строк </p>
	<textarea class="res" readonly=yes></textarea>
</div>

</body>
</html>