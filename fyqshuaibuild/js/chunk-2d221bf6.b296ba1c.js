(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d221bf6"],{cc55:function(t,e,a){"use strict";a.r(e);var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("div",{staticClass:"i-layout-page-header"},[a("PageHeader",{attrs:{title:"轮播图管理",content:"","hidden-breadcrumb":""}})],1),a("Card",{staticClass:"ivu-mt",attrs:{bordered:!1,"dis-hover":""}},[a("div",[a("div",{staticClass:"i-table-no-border"},[a("Button",{attrs:{type:"primary",icon:"md-add"},on:{click:t.handleShowCreate}},[t._v("添加轮播图分组")]),a("Table",{ref:"table",staticClass:"ivu-mt",attrs:{columns:t.columns,data:t.data.list,loading:t.loading,border:"","show-header":""},on:{"on-sort-change":t.handleSortChange},scopedSlots:t._u([{key:"create_time",fn:function(e){var a=e.row;return[t._v("\n                        "+t._s(t.$helper.getTimeStr(a.create_time))+"\n                    ")]}},{key:"shop_type",fn:function(e){var a=e.row;return[t._v("\n                        "+t._s(t.getShopType(a.shop_type))+"\n                    ")]}},{key:"action",fn:function(e){e.row;var n=e.index;return[a("a",{on:{click:function(e){return t.handleToDetail(n)}}},[t._v("详情")]),a("Divider",{attrs:{type:"vertical"}}),a("a",{on:{click:function(e){return t.handleShowUpdate(n)}}},[t._v("编辑")]),a("Divider",{attrs:{type:"vertical"}}),a("a",{on:{click:function(e){return t.handleDelete(n)}}},[t._v("删除")])]}}])}),a("div",{staticClass:"ivu-mt ivu-text-center"},[a("Page",{attrs:{total:t.data.total,current:t.data.page,"show-total":"","show-sizer":"","page-size-opts":[10,20,50,100],"show-elevator":!0},on:{"update:current":function(e){return t.$set(t.data,"page",e)},"on-change":t.handleToPage,"on-page-size-change":t.handlePageSize}})],1)],1)]),a("Modal",{attrs:{title:"添加轮播图组",loading:t.create.loading},on:{"on-ok":t.handleCreate},model:{value:t.create.show,callback:function(e){t.$set(t.create,"show",e)},expression:"create.show"}},[a("Form",{ref:"create",attrs:{model:t.create.data,rules:t.create.rules,"label-width":120}},[a("FormItem",{attrs:{label:"轮播图组名称：",prop:"name"}},[a("Input",{staticStyle:{width:"90%"},attrs:{placeholder:"请输入轮播图组名称",maxlength:16},model:{value:t.create.data.name,callback:function(e){t.$set(t.create.data,"name","string"===typeof e?e.trim():e)},expression:"create.data.name"}})],1),a("FormItem",{attrs:{label:"所属模块：",prop:"shop_type"}},[a("Select",{staticStyle:{width:"90%"},attrs:{placeholder:"请选择所属模块",clearable:""},model:{value:t.create.data.shop_type,callback:function(e){t.$set(t.create.data,"shop_type",t._n(e))},expression:"create.data.shop_type"}},[a("Option",{attrs:{value:1}},[t._v("吃")]),a("Option",{attrs:{value:2}},[t._v("喝")]),a("Option",{attrs:{value:3}},[t._v("玩乐")])],1)],1)],1)],1),a("Modal",{attrs:{title:"编辑轮播图组",loading:t.update.loading},on:{"on-ok":t.handleUpdate},model:{value:t.update.show,callback:function(e){t.$set(t.update,"show",e)},expression:"update.show"}},[a("Form",{ref:"update",attrs:{model:t.update.data,rules:t.update.rules,"label-width":120}},[a("FormItem",{attrs:{label:"轮播图组名称：",prop:"name"}},[a("Input",{staticStyle:{width:"90%"},attrs:{placeholder:"请输入轮播图组名称",maxlength:16},model:{value:t.update.data.name,callback:function(e){t.$set(t.update.data,"name","string"===typeof e?e.trim():e)},expression:"update.data.name"}})],1),a("FormItem",{attrs:{label:"所属模块：",prop:"shop_type"}},[a("Select",{staticStyle:{width:"90%"},attrs:{placeholder:"请选择所属模块",clearable:""},model:{value:t.update.data.shop_type,callback:function(e){t.$set(t.update.data,"shop_type",t._n(e))},expression:"update.data.shop_type"}},[a("Option",{attrs:{value:1}},[t._v("吃")]),a("Option",{attrs:{value:2}},[t._v("喝")]),a("Option",{attrs:{value:3}},[t._v("玩乐")])],1)],1)],1)],1)],1)],1)},r=[],i=a("a34a"),o=a.n(i),s=a("2f62");function c(t,e,a,n,r,i,o){try{var s=t[i](o),c=s.value}catch(l){return void a(l)}s.done?e(c):Promise.resolve(c).then(n,r)}function l(t){return function(){var e=this,a=arguments;return new Promise(function(n,r){var i=t.apply(e,a);function o(t){c(i,n,r,o,s,"next",t)}function s(t){c(i,n,r,o,s,"throw",t)}o(void 0)})}}function d(t,e){var a=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter(function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable})),a.push.apply(a,n)}return a}function u(t){for(var e=1;e<arguments.length;e++){var a=null!=arguments[e]?arguments[e]:{};e%2?d(a,!0).forEach(function(e){p(t,e,a[e])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(a)):d(a).forEach(function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(a,e))})}return t}function p(t,e,a){return e in t?Object.defineProperty(t,e,{value:a,enumerable:!0,configurable:!0,writable:!0}):t[e]=a,t}var h={name:"content-slider-list",data:function(){var t=this;return{formData:{keyword:"",update_time:""},rules:{},grid:{xl:8,lg:8,md:8,sm:24,xs:24},columns:[{title:"序号",width:65,render:function(e,a){return e("div",a.index+1+parseInt(t.data.page_size)*(parseInt(t.data.page)-1))}},{title:"轮播图组名称",key:"name",minWidth:500},{title:"所属模块",key:"shop_type",slot:"shop_type",minWidth:100},{title:"添加时间",key:"create_time",slot:"create_time",minWidth:120,sortable:"custom"},{title:"操作",slot:"action",align:"center",minWidth:140}],loading:!1,dict:{role:{}},data:{list:[],page:1,page_size:10,page_count:0,total:0},sort_column:"create_time",sort_direction:"DESC",create:{loading:!0,show:!1,data:{name:"",shop_type:""},rules:{name:[{required:!0,message:"请输入轮播图组名称",trigger:"blur"}],shop_type:[{required:!0,message:"请选择所属模块",trigger:"change",type:"number"}]}},update:{loading:!0,show:!1,data:{name:"",shop_type:""},rules:{name:[{required:!0,message:"请输入轮播图组名称",trigger:"blur"}],shop_type:[{required:!0,message:"请选择所属模块",trigger:"change",type:"number"}]}}}},computed:u({},Object(s["e"])("admin/layout",["isMobile"]),{labelWidth:function(){return this.isMobile?void 0:100},labelPosition:function(){return this.isMobile?"top":"right"}}),methods:{getShopType:function(t){return 1==t?"吃":2==t?"喝":"玩乐"},getList:function(){var t=this,e=arguments.length>0&&void 0!==arguments[0]&&arguments[0];this.loading=!0;var a={page_size:this.data.page_size,page:this.data.page,sort_column:this.sort_column,sort_direction:this.sort_direction};a=Object.assign(this.formData,a),a.update_time=[this.formData.update_time_begin,this.formData.update_time_end],a.dict="",this.$http.query("/Manage/SliderGroup/list",a).then(function(){var a=l(o.a.mark(function a(n){return o.a.wrap(function(a){while(1)switch(a.prev=a.next){case 0:console.log(n),e&&(t.dict=n.dict),t.data.list=n.list,t.data.total=n.total,t.data.page=n.page,t.data.page_size=n.page_size,t.data.page_count=n.page_count,t.loading=!1;case 8:case"end":return a.stop()}},a)}));return function(t){return a.apply(this,arguments)}}()).catch(function(t){console.log(t)})},handleSubmit:function(){this.getList()},handleToPage:function(t){this.data.page=t,this.getList()},handlePageSize:function(t){this.data.page_size=t,this.data.page=1,this.getList()},handleReset:function(){this.formData.update_time_begin="",this.formData.update_time_end="",this.$refs.form.resetFields()},handleToDetail:function(t){this.$router.push({path:"/content/slider-detail/".concat(this.data.list[t].group_id)})},handleShowCreate:function(){this.create.show=!0},handleDelete:function(t){var e=this;this.$Modal.confirm({title:"删除确认",content:"确认删除轮播图组吗？删除后组内的轮播图将被删除，删除后不可恢复请谨慎操作",onOk:function(){var a={group_id:e.data.list[t].group_id};e.$http.query("/Manage/SliderGroup/delete",a).then(function(){var t=l(o.a.mark(function t(a){return o.a.wrap(function(t){while(1)switch(t.prev=t.next){case 0:e.$Message.success("分组删除成功"),e.getList();case 2:case"end":return t.stop()}},t)}));return function(e){return t.apply(this,arguments)}}())}})},handleCreate:function(){var t=this;this.$refs.create.validate(function(e){if(e){var a=t.create.data;t.$http.query("/Manage/SliderGroup/add",a).then(function(){var e=l(o.a.mark(function e(a){return o.a.wrap(function(e){while(1)switch(e.prev=e.next){case 0:t.$Message.success("操作成功"),t.getList(),t.create.show=!1,t.create.loading=!1,t.$nextTick(function(){t.create.loading=!0});case 5:case"end":return e.stop()}},e)}));return function(t){return e.apply(this,arguments)}}()).catch(function(t){console.log(t)}),t.create.loading=!1,t.$nextTick(function(){t.create.loading=!0})}t.create.loading=!1,t.$nextTick(function(){t.create.loading=!0})})},handleShowUpdate:function(t){this.update.show=!0,this.update.data.group_id=this.data.list[t].group_id,this.update.data.name=this.data.list[t].name,this.update.data.shop_type=this.data.list[t].shop_type},handleUpdate:function(){var t=this;this.$refs.update.validate(function(e){if(e){var a=t.update.data;t.$http.query("/Manage/SliderGroup/update",a).then(function(){var e=l(o.a.mark(function e(a){return o.a.wrap(function(e){while(1)switch(e.prev=e.next){case 0:t.$Message.success("操作成功"),t.getList(),t.update.show=!1,t.update.loading=!1,t.$nextTick(function(){t.update.loading=!0});case 5:case"end":return e.stop()}},e)}));return function(t){return e.apply(this,arguments)}}()).catch(function(t){console.log(t)})}t.update.loading=!1,t.$nextTick(function(){t.update.loading=!0})})},handleSortChange:function(t){var e=t.key,a=t.order;this.sort_column=e,this.sort_direction=a,this.data.page=1,this.getList()}},mounted:function(){this.getList(!0)}},g=h,f=a("2877"),m=Object(f["a"])(g,n,r,!1,null,null,null);e["default"]=m.exports}}]);