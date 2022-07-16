function toolBtn(prefix,suffix) {
	var el = document.getElementById("message");
	if (document.selection) { //IE-like
		el.focus();
		document.selection.createRange().text=prefix+document.selection.createRange().text+suffix;
	} else if (typeof el.selectionStart != undefined) { //FF-like
		el.value=el.value.substring(0,el.selectionStart)+prefix+el.value.substring(el.selectionStart,el.selectionEnd)+suffix+el.value.substring(el.selectionEnd,el.value.length);
		el.focus();
	}
}

function movetid() {
	var x = document.getElementById('forumselect').selectedIndex;
	document.getElementById('move').innerHTML = document.getElementsByTagName('option')[x].value;
	return document.getElementsByTagName('option')[x].value;
}

function trashConfirm(e) {
	if (!confirm('Are you sure you want to trash this thread?')) e.preventDefault();
}

function submitmod(act) {
	document.getElementById('action').value = act;
	document.getElementById('mod').submit();
}
function submitmove(fid) {
	document.mod.arg.value = fid;
	submitmod('move')
}

function themePreview(id) {
	document.head.getElementsByTagName('link')[1].href = 'theme/'+id+'/'+id+'.css';
}
