function select1(obj,event)
{
	if(event.shiftKey && $(obj).attr('id') > 0)
	{
		if(!$('#'+($(obj).attr('id')-1)).attr('checked'))$('#'+($(obj).attr('id')-1)).attr('checked','1');
		else $('#'+($(obj).attr('id')-1)).removeAttr('checked');
		select1($('#'+($(obj).attr('id')-1)),event);
	}
	var name = $(obj).attr('name');
	if($('.'+name).hasClass('sel1'))$('.'+name).removeClass('sel1');
	else $('.'+name).addClass('sel1');
}

function select2(obj,event)
{
	if(event.shiftKey && $(obj).attr('id') > 0)
	{
		if(!$('#'+($(obj).attr('id')-1)).attr('checked'))$('#'+($(obj).attr('id')-1)).attr('checked','1');
		else $('#'+($(obj).attr('id')-1)).removeAttr('checked');
		select2($('#'+($(obj).attr('id')-1)),event);
	}
	var item = $(obj).attr('name');
	if($('.i'+item).hasClass('sel2'))$('.i'+item).removeClass('sel2');
	else $('.i'+item).addClass('sel2');
}

function zapis(obj)
{
	$('.sel1.sel2').children().val(obj.value,event);
}

function showItem(link,event)
{
	$.get(link,function(data){
		$('#image').attr('src',data.src);
		$('#image').css({ 'left':event.pageX+16,'top':event.pageY+16 });
		$('#image').show();
	});
}

function posun(obj,event)
{
	var pole = $(obj).attr('id').split('-');
	var cislo = pole[1]*1;
	if(event.keyCode == 40)$('#'+pole[0]+'-'+(cislo+1)).focus();
	if(event.keyCode == 38)$('#'+pole[0]+'-'+(cislo-1)).focus();
}

function cmdwin(message,def,link)
{
	var val = prompt(message,def);
	if(val)$.get(link,{ 'id2':val });
}

function filter(obj,link)
{
	$.post(link,{ val: obj.value });
}


