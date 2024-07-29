(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d0aeca9"],{"0c22":function(t,e,a){"use strict";a.r(e);var r=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("div",{staticClass:"i-layout-page-header"},[a("PageHeader",{attrs:{title:"商家统计",content:"","hidden-breadcrumb":""}})],1),a("Card",{staticClass:"ivu-mt",attrs:{bordered:!1,"dis-hover":""}},[a("div",[a("Form",{ref:"form",attrs:{model:t.formData,rules:t.rules,"label-width":t.labelWidth,"label-position":t.labelPosition}},[a("Row",{attrs:{gutter:24,type:"flex",justify:"end"}},[a("Col",t._b({},"Col",t.grid,!1),[a("FormItem",{attrs:{label:"关键字",prop:"keyword","label-for":"keyword"}},[a("Input",{attrs:{placeholder:"用户ID/昵称/店铺名","element-id":"keyword"},model:{value:t.formData.keyword,callback:function(e){t.$set(t.formData,"keyword",e)},expression:"formData.keyword"}})],1)],1),a("Col",t._b({},"Col",t.grid,!1),[a("FormItem",{attrs:{label:"店铺类型：",prop:"shop_type"}},[a("Select",{attrs:{placeholder:"请选择店铺类型",clearable:""},model:{value:t.formData.shop_type,callback:function(e){t.$set(t.formData,"shop_type",t._n(e))},expression:"formData.shop_type"}},[a("Option",{attrs:{value:1}},[t._v("吃")]),a("Option",{attrs:{value:2}},[t._v("喝")]),a("Option",{attrs:{value:3}},[t._v("玩乐")])],1)],1)],1),a("Col",t._b({},"Col",t.grid,!1),[a("FormItem",{attrs:{label:"统计时间",prop:"create_time"}},[a("Row",t._b({},"Row",t.grid,!1),[a("Col",{attrs:{span:"12"}},[a("DatePicker",{attrs:{type:"date","show-week-numbers":"",placeholder:"起止时间"},on:{"on-change":function(e){t.getDate("create_time_begin",e)}},model:{value:t.formData.create_time_begin,callback:function(e){t.$set(t.formData,"create_time_begin",e)},expression:"formData.create_time_begin"}})],1),a("Col",{attrs:{span:"12"}},[a("DatePicker",{staticStyle:{"margin-left":"5%"},attrs:{type:"date","show-week-numbers":"",placeholder:"终止时间"},on:{"on-change":function(e){t.getDate("create_time_end",e)}},model:{value:t.formData.create_time_end,callback:function(e){t.$set(t.formData,"create_time_end",e)},expression:"formData.create_time_end"}})],1)],1)],1)],1),a("Col",t._b({staticClass:"ivu-text-right"},"Col",t.grid,!1),[a("FormItem",[a("Button",{attrs:{type:"primary"},on:{click:t.handleSubmit}},[t._v("查询")]),a("Button",{staticClass:"ivu-ml-8",on:{click:t.handleReset}},[t._v("重置")])],1)],1)],1)],1),a("div",{staticClass:"i-table-no-border"},[a("Table",{ref:"table",staticClass:"ivu-mt",attrs:{columns:t.columns,data:t.data.list,loading:t.loading,border:"","show-header":""},on:{"on-sort-change":t.handleSortChange},scopedSlots:t._u([{key:"is_recommend",fn:function(e){var r=e.row;return[a("i-switch",{attrs:{value:1==r.is_recommend},on:{"on-change":function(e){return t.handleRecommend(r.shop_id,r.is_recommend)}}})]}},{key:"name",fn:function(e){var r=e.row;return[a("div",{staticClass:"table-div"},[""!=r.logo?a("img",{staticStyle:{height:"50px",width:"50px","border-radius":"50%"},attrs:{src:r.logo}}):t._e(),a("br"),t._v("\r\n                     "+t._s(r.name)+"\r\n                ")])]}},{key:"shop_type",fn:function(e){var a=e.row;return[t._v("\r\n                "+t._s(t.getShopType(a.shop_type))+"\r\n            ")]}},{key:"create_time",fn:function(e){var a=e.row;return[t._v("\r\n                "+t._s(t.$helper.getTimeStr(a.create_time))+"\r\n            ")]}},{key:"service_rate",fn:function(e){var a=e.row;return[t._v("\r\n               "+t._s(a.service_rate)+" %\r\n            ")]}},{key:"status",fn:function(e){var r=e.row;return[a("i-switch",{attrs:{value:1==r.status},on:{"on-change":function(e){return t.handleStatus(r.shop_id,r.status)}}})]}},{key:"action",fn:function(e){e.row;var r=e.index;return[a("a",{on:{click:function(e){return t.handleShowSort(r)}}},[t._v("修改")]),a("Divider",{attrs:{type:"vertical"}}),a("a",{on:{click:function(e){return t.handleToDetail(r)}}},[t._v("查看")])]}}])}),a("div",{staticClass:"ivu-mt ivu-text-center"},[a("Page",{attrs:{total:t.data.total,current:t.data.page,"show-total":"","show-sizer":"","page-size-opts":[10,20,50,100],"show-elevator":!0},on:{"update:current":function(e){return t.$set(t.data,"page",e)},"on-change":t.handleToPage,"on-page-size-change":t.handlePageSize}})],1)],1)],1),a("Modal",{attrs:{title:"修改排序(越大越前)",loading:t.sort.loading,width:600},on:{"on-ok":t.handleSort},model:{value:t.sort.show,callback:function(e){t.$set(t.sort,"show",e)},expression:"sort.show"}},[a("Form",{ref:"sort",attrs:{model:t.sort.data,rules:t.sort.rules,"label-width":120}},[a("FormItem",{attrs:{label:"排序数字：",prop:"sort"}},[a("Input",{staticStyle:{width:"90%"},attrs:{placeholder:"请输入排序值",maxlength:32},model:{value:t.sort.data.sort,callback:function(e){t.$set(t.sort.data,"sort",t._n(e))},expression:"sort.data.sort"}})],1),a("FormItem",{attrs:{label:"服务费率：",prop:"service_rate"}},[a("Input",{staticStyle:{width:"90%"},attrs:{placeholder:"请输入服务费率",maxlength:32},model:{value:t.sort.data.service_rate,callback:function(e){t.$set(t.sort.data,"service_rate",t._n(e))},expression:"sort.data.service_rate"}},[a("span",{attrs:{slot:"append"},slot:"append"},[t._v("%")])])],1)],1)],1)],1)],1)},n=[],i=a("a34a"),o=a.n(i),s=a("2f62");function c(t,e,a,r,n,i,o){try{var s=t[i](o),c=s.value}catch(l){return void a(l)}s.done?e(c):Promise.resolve(c).then(r,n)}function l(t){return function(){var e=this,a=arguments;return new Promise(function(r,n){var i=t.apply(e,a);function o(t){c(i,r,n,o,s,"next",t)}function s(t){c(i,r,n,o,s,"throw",t)}o(void 0)})}}function d(t,e){var a=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter(function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable})),a.push.apply(a,r)}return a}function u(t){for(var e=1;e<arguments.length;e++){var a=null!=arguments[e]?arguments[e]:{};e%2?d(a,!0).forEach(function(e){h(t,e,a[e])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(a)):d(a).forEach(function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(a,e))})}return t}function h(t,e,a){return e in t?Object.defineProperty(t,e,{value:a,enumerable:!0,configurable:!0,writable:!0}):t[e]=a,t}var p={name:"shop-shop-statistics",data:function(){var t=this;return{formData:{keyword:"",create_time:"",create_time_begin:"2020-1-1"},rules:{},grid:{xl:8,lg:8,md:8,sm:24,xs:24},columns:[{title:"序号",width:65,render:function(e,a){return e("div",a.index+1+parseInt(t.data.page_size)*(parseInt(t.data.page)-1))}},{title:"用户编号",key:"shop_id",minWidth:80},{title:"店铺名称",key:"name",slot:"name",minWidth:160},{title:"类型",key:"shop_type",slot:"shop_type",minWidth:70},{title:"服务费率",key:"service_rate",slot:"service_rate",minWidth:70},{title:"订单总数",key:"order_count",minWidth:100},{title:"已完成订单数",key:"order_finished",minWidth:100},{title:"未完成订单数",key:"order_handling",minWidth:100},{title:"已取消订单数",key:"order_canceled",minWidth:100},{title:"已完成订单金额",key:"amount_finished",minWidth:100},{title:"未完成订单金额",key:"amount_handling",minWidth:100},{title:"未取消订单金额",key:"amount_canceled",minWidth:100}],loading:!1,dict:{role:{}},data:{list:[],page:1,page_size:10,page_count:0,total:0},sort_column:"create_time",sort_direction:"DESC",sort:{loading:!0,show:!1,data:{sort:""},rules:{sort:[{required:!0,message:"请输入排序数字",trigger:"blur",type:"number"}]}}}},computed:u({},Object(s["e"])("admin/layout",["isMobile"]),{labelWidth:function(){return this.isMobile?void 0:100},labelPosition:function(){return this.isMobile?"top":"right"}}),methods:{getShopType:function(t){return 1==t?"吃":2==t?"喝":"玩乐"},getList:function(){var t=this,e=arguments.length>0&&void 0!==arguments[0]&&arguments[0];this.loading=!0;var a={page_size:this.data.page_size,page:this.data.page,sort_column:this.sort_column,sort_direction:this.sort_direction};a=Object.assign(this.formData,a),a.is_admin=0,a.create_time=[this.formData.create_time_begin,this.formData.create_time_end],e?(a.dict="",a.first=1):(a.dict="",a.first=0),this.$http.query("/Manage/Shop/statistics",a).then(function(){var a=l(o.a.mark(function a(r){return o.a.wrap(function(a){while(1)switch(a.prev=a.next){case 0:console.log(r),e&&(t.dict=r.dict),t.data.list=r.list,t.data.total=r.total,t.data.page=r.page,t.data.page_size=r.page_size,t.data.page_count=r.page_count,t.loading=!1;case 8:case"end":return a.stop()}},a)}));return function(t){return a.apply(this,arguments)}}()).catch(function(t){console.log(t)})},getDate:function(t,e){this.formData[t]=e},handleStatus:function(t,e){var a=this,r={shop_id:t,status:e?0:1};this.$http.query("/Manage/Shop/setStatus",r).then(function(){var t=l(o.a.mark(function t(e){return o.a.wrap(function(t){while(1)switch(t.prev=t.next){case 0:a.$Message.success("操作成功");case 1:case"end":return t.stop()}},t)}));return function(e){return t.apply(this,arguments)}}())},handleRecommend:function(t,e){var a=this,r={shop_id:t,is_recommend:e?0:1};this.$http.query("/Manage/Shop/setRecommend",r).then(function(){var t=l(o.a.mark(function t(e){return o.a.wrap(function(t){while(1)switch(t.prev=t.next){case 0:a.$Message.success("操作成功");case 1:case"end":return t.stop()}},t)}));return function(e){return t.apply(this,arguments)}}())},handleSubmit:function(){this.data.page=1,this.getList()},handleToPage:function(t){this.data.page=t,this.getList()},handlePageSize:function(t){this.data.page_size=t,this.data.page=1,this.getList()},handleReset:function(){this.formData.create_time_begin="",this.formData.create_time_end="",this.$refs.form.resetFields()},handleToDetail:function(t){this.$router.push({path:"/shop/shop-detail/".concat(this.data.list[t].shop_id)})},handleDelete:function(t){var e=this;this.$Modal.confirm({title:"删除确认",content:"确定删除该商家吗？",onOk:function(){var a={shop_id:e.data.list[t].shop_id};e.$http.query("/Manage/Shop/delete",a).then(function(){var t=l(o.a.mark(function t(a){return o.a.wrap(function(t){while(1)switch(t.prev=t.next){case 0:e.$Message.success("商家删除成功"),e.getList();case 2:case"end":return t.stop()}},t)}));return function(e){return t.apply(this,arguments)}}())}})},getDatePickerValue:function(t,e){this.formData[t]=e.join(",")},handleSortChange:function(t){var e=t.key,a=t.order;this.sort_column=e,this.sort_direction=a,this.data.page=1,this.getList()},handleShowSort:function(t){this.sort.show=!0,this.sort.data.shop_id=this.data.list[t].shop_id,this.sort.data.sort=this.data.list[t].sort,this.sort.data.service_rate=this.data.list[t].service_rate},handleSort:function(){var t=this;this.$refs.sort.validate(function(e){if(e){var a=t.sort.data;t.$http.query("/Manage/Shop/setSort",a).then(function(){var e=l(o.a.mark(function e(a){return o.a.wrap(function(e){while(1)switch(e.prev=e.next){case 0:t.$Message.success("操作成功"),t.sort.show=!1,t.sort.loading=!1,t.$nextTick(function(){t.sort.loading=!0}),t.getList();case 5:case"end":return e.stop()}},e)}));return function(t){return e.apply(this,arguments)}}()).catch(function(){t.sort.loading=!1,t.$nextTick(function(){t.sort.loading=!0})})}t.sort.loading=!1,t.$nextTick(function(){t.sort.loading=!0})})}},mounted:function(){var t=new Date;this.formData.create_time_begin=t.getFullYear()+"-"+t.getMonth()+"-"+t.getDate(),this.formData.create_time_end=this.formData.create_time_begin,this.getList(!0)}},m=p,f=a("2877"),g=Object(f["a"])(m,r,n,!1,null,null,null);e["default"]=g.exports}}]);