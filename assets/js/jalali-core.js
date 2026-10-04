/* Evented Jalali — dependency-free conversion/validation API (ES5-compatible). */
(function (window) {
    'use strict';
    var fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
    function div(a, b) { return Math.trunc(a / b); }
    function mod(a, b) { return a - div(a, b) * b; }
    function latin(value) { return String(value == null ? '' : value).replace(/[۰-۹٠-٩]/g, function (c) { var i = fa.indexOf(c); return String(i > -1 ? i : ar.indexOf(c)); }); }
    function persian(value) { return String(value == null ? '' : value).replace(/\d/g, function (c) { return fa[Number(c)]; }); }
    function leap(jy) { var breaks=[-61,9,38,199,426,686,756,818,1111,1181,1210,1635,2060,2097,2192,2262,2324,2394,2456,3178],jp=breaks[0],jump=0,n,i; if(jy<jp||jy>=breaks[breaks.length-1]){return false;} for(i=1;i<breaks.length;i++){var jm=breaks[i];jump=jm-jp;if(jy<jm){break;}jp=jm;} n=jy-jp;if(jump-n<6){n=n-jump+div(jump+4,33)*33;} var result=mod(mod(n+1,33)-1,4);if(result===-1){result=4;}return result===0; }
    function monthLength(y,m){return m<1||m>12?0:m<=6?31:m<=11?30:leap(y)?30:29;}
    function valid(y,m,d){y=+y;m=+m;d=+d;return y>=1&&y<=3177&&m>=1&&m<=12&&d>=1&&d<=monthLength(y,m);}
    function fromGregorian(gy,gm,gd){var offs=[0,31,59,90,120,151,181,212,243,273,304,334],gy2=gm>2?gy+1:gy,days=355666+365*gy+Math.floor((gy2+3)/4)-Math.floor((gy2+99)/100)+Math.floor((gy2+399)/400)+gd+offs[gm-1],jy=-1595+33*Math.floor(days/12053);days%=12053;jy+=4*Math.floor(days/1461);days%=1461;if(days>365){jy+=Math.floor((days-1)/365);days=(days-1)%365;}return days<186?{year:jy,month:1+Math.floor(days/31),day:1+days%31}:{year:jy,month:7+Math.floor((days-186)/30),day:1+(days-186)%30};}
    function toGregorian(jy,jm,jd){if(!valid(jy,jm,jd)){return null;}var gy=jy<=979?621:1600; jy-=jy<=979?0:979;var days=365*jy+8*Math.floor(jy/33)+Math.floor((jy%33+3)/4)+78+jd+(jm<7?31*(jm-1):30*(jm-7)+186);gy+=400*Math.floor(days/146097);days%=146097;if(days>36524){gy+=100*Math.floor(--days/36524);days%=36524;if(days>=365){days++;}}gy+=4*Math.floor(days/1461);days%=1461;if(days>365){gy+=Math.floor((days-1)/365);days=(days-1)%365;}var gd=days+1,months=[0,31,(gy%4===0&&gy%100!==0)||gy%400===0?29:28,31,30,31,30,31,31,30,31,30,31],gm;for(gm=1;gm<=12&&gd>months[gm];gm++){gd-=months[gm];}return{year:gy,month:gm,day:gd};}
    function parse(value){var m=latin(value).trim().match(/^(\d{3,4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})(?:[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/);if(!m){return null;}var p={year:+m[1],month:+m[2],day:+m[3],hour:+(m[4]||0),minute:+(m[5]||0),second:+(m[6]||0)};return valid(p.year,p.month,p.day)&&p.hour<24&&p.minute<60&&p.second<60?p:null;}
    function pad(n){n=Number(n)||0;return n<10?'0'+n:String(n);}
    function format(parts, withTime, usePersian){if(!parts){return '';}var out=parts.year+'/'+pad(parts.month)+'/'+pad(parts.day)+(withTime?' '+pad(parts.hour||0)+':'+pad(parts.minute||0)+(parts.second!=null?':'+pad(parts.second):''):'');return usePersian?persian(out):out;}
    window.EventedJalali={toLatinDigits:latin,toPersianDigits:persian,isLeapYear:leap,monthLength:monthLength,isValid:valid,parse:parse,format:format,toGregorian:toGregorian,fromGregorian:fromGregorian};
}(window));
