(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-7495fbb4"],{"0042":function(t,e,a){},"78e3":function(t,e,a){"use strict";a.r(e);var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("div",{staticClass:"i-layout-page-header"},[a("PageHeader",{attrs:{title:"标签管理",content:"","hidden-breadcrumb":""}})],1),a("Card",{staticClass:"ivu-mt",attrs:{bordered:!1,"dis-hover":""}},[a("div",[a("div",{staticClass:"i-table-no-border"},[a("Button",{directives:[{name:"auth",rawName:"v-auth",value:["admin"],expression:"['admin']"}],attrs:{type:"primary",icon:"md-add"},on:{click:t.handleShowCreate}},[t._v("新建")]),a("Table",{ref:"table",staticClass:"ivu-mt",attrs:{columns:t.columns,data:t.data.list,loading:t.loading,border:"","show-header":""},on:{"on-sort-change":t.handleSortChange},scopedSlots:t._u([{key:"actions",fn:function(e){var n=e.row;return[a("div",{staticClass:"table-div"},[t._v("\n                  "+t._s(1==n.action?"添加":"减少")+"\n                ")])]}},{key:"action",fn:function(e){e.row;var n=e.index;return[a("a",{on:{click:function(e){return t.handleShowUpdate(n)}}},[a("Divider",{attrs:{type:"vertical"}}),t._v("编辑")],1),a("a",{on:{click:function(e){return t.handleDelete(n)}}},[a("Divider",{attrs:{type:"vertical"}}),t._v("删除")],1)]}}])}),a("div",{staticClass:"ivu-mt ivu-text-center"},[a("Page",{attrs:{total:t.data.total,current:t.data.page,"show-total":"","show-sizer":"","page-size-opts":[10,20,50,100],"show-elevator":!0},on:{"update:current":function(e){return t.$set(t.data,"page",e)},"on-change":t.handleToPage,"on-page-size-change":t.handlePageSize}})],1)],1)])]),a("Modal",{attrs:{title:"添加标签",loading:t.create.loading,width:600},on:{"on-ok":t.handleCreate},model:{value:t.create.show,callback:function(e){t.$set(t.create,"show",e)},expression:"create.show"}},[a("Form",{ref:"create",attrs:{model:t.create.data,rules:t.create.rules,"label-width":120}},[a("FormItem",{attrs:{label:"名称：",prop:"name"}},[a("Input",{staticStyle:{width:"90%"},attrs:{placeholder:"请输入名称",maxlength:32},model:{value:t.create.data.name,callback:function(e){t.$set(t.create.data,"name","string"===typeof e?e.trim():e)},expression:"create.data.name"}})],1)],1)],1),a("Modal",{attrs:{title:"修改标签",loading:t.update.loading,width:600},on:{"on-ok":t.handleUpdate},model:{value:t.update.show,callback:function(e){t.$set(t.update,"show",e)},expression:"update.show"}},[a("Form",{ref:"update",staticStyle:{"padding-right":"10px"},attrs:{model:t.update.data,rules:t.update.rules,"label-width":100}},[a("FormItem",{attrs:{label:"名称：",prop:"name"}},[a("Input",{staticStyle:{width:"90%"},attrs:{placeholder:"请输入名称",maxlength:32},model:{value:t.update.data.name,callback:function(e){t.$set(t.update.data,"name","string"===typeof e?e.trim():e)},expression:"update.data.name"}})],1)],1)],1)],1)},i=[],r=a("a34a"),s=a.n(r),o=a("2f62");function d(t,e,a,n,i,r,s){try{var o=t[r](s),d=o.value}catch(c){return void a(c)}o.done?e(d):Promise.resolve(d).then(n,i)}function c(t){return function(){var e=this,a=arguments;return new Promise(function(n,i){var r=t.apply(e,a);function s(t){d(r,n,i,s,o,"next",t)}function o(t){d(r,n,i,s,o,"throw",t)}s(void 0)})}}function u(t,e){var a=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter(function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable})),a.push.apply(a,n)}return a}function l(t){for(var e=1;e<arguments.length;e++){var a=null!=arguments[e]?arguments[e]:{};e%2?u(a,!0).forEach(function(e){h(t,e,a[e])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(a)):u(a).forEach(function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(a,e))})}return t}function h(t,e,a){return e in t?Object.defineProperty(t,e,{value:a,enumerable:!0,configurable:!0,writable:!0}):t[e]=a,t}var p={name:"system-address-list",data:function(){return{formData:{keyword:"",status:""},rules:{},grid:{xl:8,lg:8,md:8,sm:24,xs:24},columns:[{title:"Id",key:"address_id",minWidth:80},{title:"名称",key:"name",minWidth:400},{title:"操作",slot:"action",align:"center",minWidth:90}],loading:!1,dict:{},data:{list:[],page:1,page_size:10,page_count:0,total:0},sort_column:"create_time",sort_direction:"ASC",create:{loading:!0,show:!1,data:{name:""},rules:{name:[{required:!0,message:"请填写名称",trigger:"blur"}]}},update:{loading:!0,show:!1,data:{address_id:0,name:""},rules:{name:[{required:!0,message:"请填写名称",trigger:"blur"}]}}}},computed:l({},Object(o["e"])("admin/layout",["isMobile"]),{labelWidth:function(){return this.isMobile?void 0:120},labelPosition:function(){return this.isMobile?"top":"right"}}),methods:{getList:function(){var t=this,e=arguments.length>0&&void 0!==arguments[0]&&arguments[0];this.loading=!0;var a={page_size:this.data.page_size,page:this.data.page,sort_column:this.sort_column,sort_direction:this.sort_direction};a=Object.assign(this.formData,a),a.dict="",this.$http.query("/Manage/Address/list",a).then(function(){var a=c(s.a.mark(function a(n){return s.a.wrap(function(a){while(1)switch(a.prev=a.next){case 0:console.log(n),e&&(t.dict=n.dict),t.data.list=n.list,t.data.total=n.total,t.data.page=n.page,t.data.page_size=n.page_size,t.data.page_count=n.page_count,t.loading=!1;case 8:case"end":return a.stop()}},a)}));return function(t){return a.apply(this,arguments)}}()).catch(function(t){console.log(t)})},handleReset:function(){this.$refs.form.resetFields()},handleSubmit:function(){this.data.page=1,this.getList()},handleToPage:function(t){this.data.page=t,this.getList()},handlePageSize:function(t){this.data.page_size=t,this.data.page=1,this.getList()},handleDelete:function(t){var e=this;this.$Modal.confirm({title:"删除记录",content:"确定删除该记录吗？",onOk:function(){var a={address_id:e.data.list[t].address_id};e.$http.query("/Manage/Address/delete",a).then(function(){var t=c(s.a.mark(function t(a){return s.a.wrap(function(t){while(1)switch(t.prev=t.next){case 0:e.$Message.success("删除成功"),e.getList();case 2:case"end":return t.stop()}},t)}));return function(e){return t.apply(this,arguments)}}())}})},handleSortChange:function(t){var e=t.key,a=t.order;this.sort_column=e,this.sort_direction=a,this.data.page=1,this.getList()},getDate:function(t,e){this.formData[t]=e},handleShowCreate:function(){this.create.show=!0},handleCreate:function(){var t=this;this.$refs.create.validate(function(e){if(e){var a=t.create.data;t.$http.query("/Manage/Address/add",a).then(function(){var e=c(s.a.mark(function e(a){return s.a.wrap(function(e){while(1)switch(e.prev=e.next){case 0:t.$Message.success("添加成功"),t.create.show=!1,t.create.loading=!1,t.$refs.create.resetFields(),t.$nextTick(function(){t.create.loading=!0}),t.getList();case 6:case"end":return e.stop()}},e)}));return function(t){return e.apply(this,arguments)}}()).catch(function(){t.create.loading=!1,t.$nextTick(function(){t.create.loading=!0})})}t.create.loading=!1,t.$nextTick(function(){t.create.loading=!0})})},handleShowUpdate:function(t){this.update.show=!0,this.update.data.address_id=this.data.list[t].address_id,this.update.data.name=this.data.list[t].name},handleUpdate:function(){var t=this;this.$refs.update.validate(function(e){if(e){var a=t.update.data;t.$http.query("/Manage/Address/update",a).then(function(){var e=c(s.a.mark(function e(a){return s.a.wrap(function(e){while(1)switch(e.prev=e.next){case 0:t.$Message.success("修改成功"),t.update.show=!1,t.update.loading=!1,t.$refs.update.resetFields(),t.$nextTick(function(){t.update.loading=!0}),t.getList();case 6:case"end":return e.stop()}},e)}));return function(t){return e.apply(this,arguments)}}()).catch(function(){t.update.loading=!1,t.$nextTick(function(){t.update.loading=!0})})}t.update.loading=!1,t.$nextTick(function(){t.update.loading=!0})})}},mounted:function(){this.getList(!0)}},f=p,g=(a("b5f54"),a("2877")),m=Object(g["a"])(f,n,i,!1,null,null,null);e["default"]=m.exports},b5f54:function(t,e,a){"use strict";var n=a("0042"),i=a.n(n);i.a}}]);