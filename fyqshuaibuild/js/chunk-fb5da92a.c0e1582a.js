(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-fb5da92a","chunk-daf1b11c"],{"2e55":function(t,e,n){"use strict";var a=n("4774"),r=n.n(a);r.a},4233:function(t,e,n){},4774:function(t,e,n){},"8cfa":function(t,e,n){"use strict";n.r(e);var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",[n("div",{staticClass:"i-layout-page-header"},[n("PageHeader",{attrs:{title:"公告添加",content:"","hidden-breadcrumb":""}})],1),n("Card",{staticClass:"ivu-mt-16",attrs:{bordered:!1,"dis-hover":""}},[n("div",{staticStyle:{width:"900px","margin-top":"20px"}},[n("Form",{ref:"create",attrs:{model:t.create.data,rules:t.create.rules,"label-width":150}},[n("FormItem",{attrs:{label:"公告内容：",prop:"content"}},[n("Input",{staticStyle:{width:"100%"},attrs:{type:"textarea",rows:8,placeholder:"请输入公告内容"},model:{value:t.create.data.content,callback:function(e){t.$set(t.create.data,"content","string"===typeof e?e.trim():e)},expression:"create.data.content"}}),n("WordCount",{attrs:{value:t.create.data.content,total:100}})],1),n("FormItem",{attrs:{label:"关联用户角色：",prop:"for_user_type"}},[n("Select",{staticStyle:{width:"40%"},attrs:{placeholder:"请选择关联用户角色",clearable:""},model:{value:t.create.data.for_user_type,callback:function(e){t.$set(t.create.data,"for_user_type",t._n(e))},expression:"create.data.for_user_type"}},[n("Option",{attrs:{value:0}},[t._v("顾客")]),n("Option",{attrs:{value:10}},[t._v("商户")])],1)],1),n("FormItem",{attrs:{label:"生效时间：",prop:"content"}},[n("DatePicker",{staticStyle:{width:"40%"},attrs:{type:"datetime","show-week-numbers":"",placeholder:"请选择生效时间"},model:{value:t.create.data.from_time,callback:function(e){t.$set(t.create.data,"from_time",e)},expression:"create.data.from_time"}})],1),n("FormItem",{attrs:{label:""}},[n("Button",{attrs:{type:"primary"},on:{click:t.handleSubmit}},[t._v("立即提交")])],1)],1)],1)])],1)},r=[],i=n("a34a"),o=n.n(i),c=n("af03");function s(t,e,n,a,r,i,o){try{var c=t[i](o),s=c.value}catch(u){return void n(u)}c.done?e(s):Promise.resolve(s).then(a,r)}function u(t){return function(){var e=this,n=arguments;return new Promise(function(a,r){var i=t.apply(e,n);function o(t){s(i,a,r,o,c,"next",t)}function c(t){s(i,a,r,o,c,"throw",t)}o(void 0)})}}var l={name:"content-notice-add",components:{Content:c["default"]},data:function(){return{dict:{},create:{data:{from_time:"",content:"",for_user_type:""},rules:{from_time:[{required:!0,message:"请选择生效时间",trigger:"change"}],for_user_type:[{required:!0,message:"请选择关联用户角色",trigger:"change"}],content:[{required:!0,message:"请输入公告内容",trigger:"blur"}]}}}},computed:{},provide:function(){return{create:this.create}},methods:{toDetailtoDetail:function(t){this.$router.push({path:"/notice/notice-update/"+t})},handleSubmit:function(){var t=this,e=this.create.data;this.$http.query("/Manage/Notice/add",e).then(function(){var e=u(o.a.mark(function e(n){return o.a.wrap(function(e){while(1)switch(e.prev=e.next){case 0:t.$Message.success("公告添加成功"),t.toDetailtoDetail(n.notice_id);case 2:case"end":return e.stop()}},e)}));return function(t){return e.apply(this,arguments)}}()).catch(function(t){console.log(t)})}},mounted:function(){}},d=l,f=(n("b436"),n("2877")),h=Object(f["a"])(d,a,r,!1,null,null,null);e["default"]=h.exports},af03:function(t,e,n){"use strict";n.r(e);var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",[n("div",{staticClass:"edit_container"},[n("Uediter",{ref:"ue",attrs:{text:t.create.data.content,config:t.ueditor.config},on:{contentChange:t.contentChangeFunc}})],1)])},r=[],i=n("b713"),o={components:{Uediter:i["a"]},data:function(){return{ueditor:{text:"",config:{initialFrameWidth:400,initialFrameHeight:500}}}},created:function(){},inject:["create"],methods:{getContent:function(){},contentChangeFunc:function(t){this.create.data.content=t}}},c=o,s=(n("2e55"),n("2877")),u=Object(s["a"])(c,a,r,!1,null,"dc7f51c2",null);e["default"]=u.exports},b436:function(t,e,n){"use strict";var a=n("4233"),r=n.n(a);r.a},b713:function(t,e,n){"use strict";var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",[n("script",{attrs:{id:"editor",type:"text/plain"}}),t.isupload?n("Upload",{attrs:{config:{total:9},isupload:t.isupload},on:{returnImgs:t.returnImgsFunc}},[t._v("上传图片")]):t._e()],1)},r=[],i=n("6d4b"),o={components:{Upload:i["a"]},name:"ue",data:function(){return{editor:null,isupload:!1,hasCallback:!1,callback:null,this_config:{autoFloatEnabled:!1}}},props:{text:String,config:Object},created:function(){window.openUpload=this.openUpload},watch:{},mounted:function(){var t=this;Object.assign(this.this_config,this.config),this.editor=window.UE.getEditor("editor",this.this_config),this.editor.addListener("ready",function(e){t.editor.setContent(t.text)}),this.editor.addListener("contentChange",function(e){t.$emit("contentChange",t.editor.getContent())})},methods:{getUEContent:function(){return this.editor.getContent()},openUpload:function(t){this.isupload=!0,t&&(this.hasCallback=!0,this.callback=t)},returnImgsFunc:function(t){null!=t&&this.hasCallback&&this.callback(t),this.isupload=!1}},destroyed:function(){this.editor.destroy()}},c=o,s=n("2877"),u=Object(s["a"])(c,a,r,!1,null,null,null);e["a"]=u.exports}}]);