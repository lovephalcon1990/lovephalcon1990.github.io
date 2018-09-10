/**
 * Created by YuemingZeng on 2017/8/9.
 */

function toThousands(num) {
	n = Number(num).toFixed(2);
	s = n.toString().split('.');
	return s[0].replace(/(\d)(?=(\d{3})+$)/g,'$1,')+'.'+s[1];
};


function checkpwd(s){
	reg = /(?!\d+$)(?![a-zA-Z]+$)[0-9a-zA-Z]+/;
	s.matches(reg);
}