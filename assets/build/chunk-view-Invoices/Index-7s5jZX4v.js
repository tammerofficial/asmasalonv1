import{f as b,g as gt,i as zt,l as jt,w as Kt,c as _,o as p,d as e,b as o,p as i,u as t,a5 as ot,K as Lt,v as C,O as f,C as d,t as r,Y as L,Z as x,a0 as Wt,a1 as Bt,$ as D,_ as W,q as B,a2 as bt,F as O,k as Q,n as st,a as U,X as lt,a4 as Ot,a6 as it,a7 as nt,a8 as rt,a9 as ct}from"../chunk-vendor-vue-DxcSX1Jd.js";import{u as Qt,a as I}from"../chunk-component-NotificationsBell-eCwis6PW.js";import{P as Rt}from"../chunk-component-PageHeader-BsA6C9en.js";import{S as R}from"../chunk-component-StatCard-AHk13FwB.js";import{_ as Gt,C as ht}from"../chunk-component-Card-BWbCGpFM.js";import{E as Ht}from"../chunk-component-EmptyState-B78c8qVh.js";import{L as yt}from"../chunk-component-LoadingSpinner-PYO1Yq7-.js";import{a as Xt}from"../chunk-view-Bookings/BookingAppearance-CtCwBfZ1.js";import"../chunk-vendor-BwxB3d-x.js";import"../chunk-vendor-charts-CNHMDSF7.js";import"../chunk-vendor-pinia-DRDRCEXO.js";import"../chunk-vendor-axios-B9ygI19o.js";import"../chunk-component-Sidebar-DOq58Wm8.js";import"../chunk-view-Bookings/BookingFormPreview-BJuYIHYB.js";const Yt={class:"invoices-page"},Zt={class:"stats-grid"},Jt={value:""},te={value:"paid"},ee={key:2,class:"table-wrapper"},ae={class:"table-header-row"},oe={class:"th-invoice"},se={class:"th-customer"},le={class:"th-amount"},ie={class:"th-status"},ne={class:"th-date"},re={class:"th-actions"},ce=["data-invoice-id"],de={class:"td-id"},ue={class:"invoice-id-badge"},me={class:"td-invoice"},pe={class:"invoice-number"},ve={class:"td-customer"},_e={class:"invoice-customer-cell"},fe={class:"customer-name"},ge=["href"],be={class:"td-amount"},he={class:"unified-amount"},ye={class:"td-paid"},xe={class:"td-due"},we={class:"td-status"},ke={class:"td-date"},Ce={class:"date-cell"},$e={class:"td-actions"},Ve={class:"actions-group"},De=["onClick","title"],Ne=["onClick","title"],Ie=["onClick","title"],Se=["onClick","title"],qe={key:0,class:"d-flex justify-content-between align-items-center"},Te={class:"text-muted"},Ue={key:1,class:"invoice-details-view"},Ae={class:"invoice-details-header"},Fe={class:"invoice-details-avatar"},Pe={class:"invoice-details-info"},Me={class:"invoice-customer-name"},Ee={class:"invoice-meta"},ze={class:"meta-item"},je={key:0,class:"meta-item"},Ke={class:"invoice-details-actions"},Le={class:"invoice-details-stats"},We={class:"stat-item"},Be={class:"stat-content"},Oe={class:"stat-label"},Qe={class:"stat-value"},Re={class:"stat-item"},Ge={class:"stat-content"},He={class:"stat-value"},Xe={class:"stat-item"},Ye={class:"stat-content"},Ze={class:"stat-value"},Je={class:"invoice-items-card"},ta={key:0,class:"text-muted"},ea={value:"paid"},aa={value:""},oa=["value"],sa={class:"create-items"},la={class:"d-flex justify-content-between align-items-center mb-2 mt-4"},ia={class:"totals-box"},na={class:"totals-row"},ra={class:"totals-row"},ca={__name:"Index",setup(da){const{t:l}=Qt(),A=jt(),k=Xt(),V=b([]),G=b(!1),H=b(!1),X=b(null),Y=b(!1),g=b(null),E=b(!1),Z=b(!1),S=b(null),z=b(!1),F=b(!1),J=b([]),h=b({search:"",status:"",date_from:"",date_to:"",customer_id:""}),m=b({current_page:1,per_page:20,total:0,total_pages:0}),j=gt(()=>{const n=V.value.length,a=V.value.filter(c=>c.status==="paid").length,s=V.value.filter(c=>c.status!=="paid"&&c.status!=="draft").length,u=V.value.reduce((c,q)=>c+(parseFloat(q.total)||0),0);return{total:n,paid:a,unpaid:s,totalAmount:u}}),$=n=>new Intl.NumberFormat("en-KW",{style:"currency",currency:"KWD",minimumFractionDigits:3}).format(n||0),xt=n=>{if(!n&&n!==0)return"0 KWD";const a=parseFloat(n);return a>=1e3?`${(a/1e3).toFixed(1)}K KWD`:`${a.toFixed(0)} KWD`},dt=n=>n?new Date(n).toLocaleDateString("en-US",{year:"numeric",month:"short",day:"numeric"}):"-",wt=n=>({draft:"Draft",sent:"Sent",paid:l("invoices.paid"),partial:"Partially Paid",overdue:"Overdue",cancelled:"Cancelled"})[n]||n,kt=n=>({draft:"cil-file",sent:"cil-paper-plane",paid:"cil-check-circle",partial:"cil-clock",overdue:"cil-warning",cancelled:"cil-x-circle"})[n]||"cil-info";let ut;const Ct=()=>{clearTimeout(ut),ut=setTimeout(()=>{m.value.current_page=1,y()},500)},y=async()=>{var n;G.value=!0;try{const a={page:m.value.current_page,per_page:m.value.per_page,...h.value};Object.keys(a).forEach(c=>{a[c]===""&&delete a[c]});const s=await I.get("/invoices",{params:a,noCache:!0}),u=((n=s.data)==null?void 0:n.data)||s.data||{};if(V.value=u.items||[],m.value=u.pagination||m.value,A.query.invoice_id){X.value=Number(A.query.invoice_id),await Lt();const c=document.querySelector(`[data-invoice-id="${X.value}"]`);c&&c.scrollIntoView({behavior:"smooth",block:"center"})}}catch(a){console.error("Error loading invoices:",a),k.error(l("common.errorLoading")),V.value=[],m.value={current_page:1,per_page:20,total:0,total_pages:0}}finally{G.value=!1}},$t=n=>{m.value.current_page=n,y()},Vt=()=>{h.value={search:"",status:"",date_from:"",date_to:"",customer_id:""},m.value.current_page=1,y()},Dt=()=>{console.log("Exporting invoices data..."),alert(l("common.export")+" - "+l("invoices.title"))},Nt=async n=>{if(confirm(`${l("invoices.markPaid")}: ${n.invoice_number||n.id}?`))try{await I.put(`/invoices/${n.id}`,{paid_amount:Number(n.total||0)}),k.success(l("invoices.paid")),clearCache("/invoices"),clearCache("/payments"),y()}catch(a){console.error("Error marking invoice as paid:",a),k.error(l("common.errorLoading"))}},mt=()=>{Y.value=!1,g.value=null,E.value=!1},It=async n=>{var a;Y.value=!0,E.value=!0,g.value={...n,items:[]};try{const s=await I.get(`/invoices/${n.id}`,{noCache:!0}),u=((a=s.data)==null?void 0:a.data)||s.data||{};g.value={...n,...u,customer_name:u.customer_name||n.customer_name,customer_phone:u.customer_phone||n.customer_phone,items:Array.isArray(u.items)?u.items:[]}}catch(s){console.error("Error loading invoice details:",s),k.error(l("common.errorLoading"))}finally{E.value=!1}},N=b({status:"sent",paid_amount:0}),St=n=>{S.value=n,N.value={status:n.status||"sent",paid_amount:Number(n.paid_amount||0)},Z.value=!0},tt=()=>{Z.value=!1,S.value=null,z.value=!1},qt=async()=>{if(S.value){z.value=!0;try{await I.put(`/invoices/${S.value.id}`,{status:N.value.status,paid_amount:Number(N.value.paid_amount||0)}),k.success(l("common.save")),clearCache("/invoices"),clearCache("/payments"),tt(),y()}catch(n){console.error("Save invoice error:",n),k.error(l("common.errorLoading"))}finally{z.value=!1}}},pt=async n=>{var a,s;try{const u=await I.get(`/invoices/${n.id}`,{noCache:!0}),c=((a=u.data)==null?void 0:a.data)||u.data||{},q=Array.isArray(c.items)?c.items:[],K=n.customer_name||"Customer",_t=c.invoice_number||n.invoice_number||n.id,Pt=c.issue_date||n.issue_date||"",ft=(typeof AsmaaSalonConfig<"u"&&AsmaaSalonConfig?AsmaaSalonConfig:{}).logoUrl||"https://asmaaljarallah.com/wp-content/uploads/2025/03/logoDark.png",Mt="Asmaaljarallah",P=(((s=document==null?void 0:document.documentElement)==null?void 0:s.getAttribute("dir"))||"ltr")==="rtl",w=P?{invoice:"فاتورة",invoiceNo:"رقم الفاتورة",date:"التاريخ",customer:"العميلة",description:"الوصف",qty:"الكمية",unit:"السعر",total:"الإجمالي",subtotal:"المجموع",discount:"الخصم",grandTotal:"الإجمالي النهائي",thanks:"شكراً لزيارتكم 💛"}:{invoice:"Invoice",invoiceNo:"Invoice No",date:"Date",customer:"Customer",description:"Description",qty:"Qty",unit:"Unit",total:"Total",subtotal:"Subtotal",discount:"Discount",grandTotal:"Total",thanks:"Thank you for your visit"},Et=`
      <html>
        <head>
          <meta charset="utf-8" />
          <title>${w.invoice} ${_t}</title>
          <style>
            @page { size: A4; margin: 14mm; }
            html, body { height: auto; }
            body {
              font-family: Arial, sans-serif;
              padding: 0;
              margin: 0;
              color: #111;
              ${P?"direction: rtl;":"direction: ltr;"}
            }
            .paper {
              padding: 14mm;
            }
            .top {
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 16px;
              padding-bottom: 12px;
              border-bottom: 2px solid #BBA07A;
            }
            .brand {
              display: flex;
              align-items: center;
              gap: 12px;
            }
            .logo {
              width: 64px;
              height: 64px;
              border-radius: 14px;
              object-fit: contain;
              background: #fff;
              border: 1px solid #eee;
              padding: 6px;
            }
            .brand h1 {
              margin: 0;
              font-size: 18px;
              font-weight: 800;
              letter-spacing: 0.2px;
            }
            .brand .sub {
              margin-top: 2px;
              font-size: 12px;
              color: #666;
            }
            .meta-box {
              min-width: 240px;
              border: 1px solid #eee;
              border-radius: 12px;
              padding: 10px 12px;
              background: #fcfbfa;
            }
            .meta-row {
              display: flex;
              justify-content: space-between;
              gap: 12px;
              font-size: 12px;
              padding: 4px 0;
            }
            .meta-row .k { color: #666; font-weight: 700; }
            .meta-row .v { color: #111; font-weight: 800; }
            .divider {
              height: 10px;
            }
            table {
              width: 100%;
              border-collapse: separate;
              border-spacing: 0;
              margin-top: 14px;
              border: 1px solid #eee;
              border-radius: 12px;
              overflow: hidden;
            }
            thead th {
              background: linear-gradient(135deg, rgba(187,160,122,0.18) 0%, rgba(187,160,122,0.08) 100%);
              color: #111;
              font-size: 12px;
              text-align: ${P?"right":"left"};
              padding: 10px 12px;
              border-bottom: 1px solid #eee;
              font-weight: 800;
            }
            tbody td {
              padding: 10px 12px;
              font-size: 12px;
              border-bottom: 1px solid #f1f1f1;
              vertical-align: top;
            }
            tbody tr:last-child td { border-bottom: none; }
            .num { text-align: ${P?"left":"right"}; white-space: nowrap; }
            .desc { color: #111; font-weight: 700; }
            .totals {
              margin-top: 14px;
              display: flex;
              justify-content: ${P?"flex-start":"flex-end"};
            }
            .totals .box {
              width: 320px;
              border: 1px solid #eee;
              border-radius: 12px;
              padding: 10px 12px;
              background: #fcfbfa;
            }
            .totals .row {
              display: flex;
              justify-content: space-between;
              gap: 12px;
              padding: 6px 0;
              font-size: 12px;
            }
            .totals .row .k { color: #666; font-weight: 800; }
            .totals .row .v { font-weight: 900; }
            .totals .grand {
              margin-top: 6px;
              padding-top: 10px;
              border-top: 1px dashed rgba(187,160,122,0.6);
              font-size: 14px;
            }
            .footer {
              margin-top: 18px;
              display: flex;
              justify-content: center;
              color: #666;
              font-size: 12px;
              padding-top: 10px;
              border-top: 1px solid #eee;
            }
          </style>
        </head>
        <body>
          <div class="paper">
            <div class="top">
              <div class="brand">
                ${ft?`<img class="logo" src="${ft}" alt="Logo" />`:""}
                <div>
                  <h1>${Mt}</h1>
                  <div class="sub">${w.invoice}</div>
                </div>
              </div>
              <div class="meta-box">
                <div class="meta-row"><span class="k">${w.invoiceNo}</span><span class="v">${_t}</span></div>
                <div class="meta-row"><span class="k">${w.date}</span><span class="v">${Pt}</span></div>
                <div class="meta-row"><span class="k">${w.customer}</span><span class="v">${K}</span></div>
              </div>
            </div>

          <table>
            <thead>
              <tr>
                <th>${w.description}</th>
                <th style="width:80px;" class="num">${w.qty}</th>
                <th style="width:120px;" class="num">${w.unit}</th>
                <th style="width:120px;" class="num">${w.total}</th>
              </tr>
            </thead>
            <tbody>
              ${q.map(M=>`
                <tr>
                  <td class="desc">${(M.description??"").toString()}</td>
                  <td class="num">${M.quantity??1}</td>
                  <td class="num">${Number(M.unit_price??0).toFixed(3)} KWD</td>
                  <td class="num">${Number(M.total??0).toFixed(3)} KWD</td>
                </tr>
              `).join("")}
            </tbody>
          </table>

          <div class="totals">
            <div class="box">
              <div class="row"><span class="k">${w.subtotal}</span><span class="v">${Number(c.subtotal??n.subtotal??0).toFixed(3)} KWD</span></div>
              <div class="row"><span class="k">${w.discount}</span><span class="v">${Number(c.discount??n.discount??0).toFixed(3)} KWD</span></div>
              <div class="row grand"><span class="k">${w.grandTotal}</span><span class="v">${Number(c.total??n.total??0).toFixed(3)} KWD</span></div>
            </div>
          </div>

          <div class="footer">${w.thanks}</div>
          </div>
        </body>
      </html>
    `,T=window.open("","_blank");if(!T){k.error("Popup blocked");return}T.document.open(),T.document.write(Et),T.document.close(),setTimeout(()=>{try{T.focus(),T.print()}catch{}},250)}catch(u){console.error("Print invoice error:",u),k.error(l("common.errorLoading"))}},et=()=>{H.value=!1,F.value=!1},vt=async()=>{H.value=!0,await Tt()},Tt=async()=>{var n;if(!(J.value.length>0))try{const a=await I.get("/customers",{params:{per_page:100},noCache:!0}),s=((n=a.data)==null?void 0:n.data)||a.data||{};J.value=s.items||[]}catch(a){console.error("Error loading customers for invoice:",a)}},v=b({customer_id:"",issue_date:new Date().toISOString().slice(0,10),status:"draft",items:[{description:"",quantity:1,unit_price:0}]}),at=gt(()=>{const n=(v.value.items||[]).reduce((a,s)=>{const u=Number(s.quantity||0),c=Number(s.unit_price||0);return a+u*c},0);return{subtotal:n,total:n}}),Ut=()=>{v.value.items.push({description:"",quantity:1,unit_price:0})},At=n=>{v.value.items.length<=1||v.value.items.splice(n,1)},Ft=async()=>{if(!v.value.customer_id){k.error(`${l("invoices.customer")} is required`);return}F.value=!0;try{const n=at.value.subtotal;if(n<=0){k.error("Total must be greater than 0"),F.value=!1;return}const a=v.value.items.map(c=>{const q=Number(c.quantity||0),K=Number(c.unit_price||0);return{description:(c.description||"").toString(),quantity:q,unit_price:K,total:q*K}}).filter(c=>c.description||c.total>0),s={customer_id:Number(v.value.customer_id),issue_date:v.value.issue_date,status:v.value.status,subtotal:n,discount:0,tax:0,total:n,items:a},u=await I.post("/invoices",s);k.success(l("common.save")),clearCache("/invoices"),clearCache("/payments"),et(),v.value={customer_id:"",issue_date:new Date().toISOString().slice(0,10),status:"draft",items:[{description:"",quantity:1,unit_price:0}]},y()}catch(n){console.error("Create invoice error:",n),k.error(l("common.errorLoading"))}finally{F.value=!1}};return zt(()=>{A.query.customer_id&&(h.value.customer_id=String(A.query.customer_id)),y()}),Kt(()=>A.query.customer_id,n=>{n&&(h.value.customer_id=String(n),m.value.current_page=1,y())}),(n,a)=>(p(),_("div",Yt,[e(Rt,{title:t(l)("invoices.title"),subtitle:t(l)("invoices.subtitle")||t(l)("invoices.title")+" - "+t(l)("dashboard.subtitle")},{icon:i(()=>[e(t(d),{icon:"cil-file"})]),actions:i(()=>[e(t(C),{color:"secondary",variant:"outline",onClick:Dt,class:"me-2 export-btn"},{default:i(()=>[e(t(d),{icon:"cil-cloud-download",class:"me-2"}),f(" "+r(t(l)("common.export")),1)]),_:1}),e(t(C),{color:"primary",class:"btn-primary-custom",onClick:vt},{default:i(()=>[e(t(d),{icon:"cil-plus",class:"me-2"}),f(" "+r(t(l)("invoices.title"))+" "+r(t(l)("common.new")),1)]),_:1})]),_:1},8,["title","subtitle"]),o("div",Zt,[e(R,{label:t(l)("invoices.title"),value:j.value.total||V.value.length,"badge-variant":"info",color:"gold",clickable:!0,onClick:a[0]||(a[0]=()=>{h.value.status="",m.value.current_page=1,y()})},{icon:i(()=>[e(t(d),{icon:"cil-file"})]),_:1},8,["label","value"]),e(R,{label:t(l)("invoices.paid"),value:j.value.paid,badge:t(l)("invoices.paid"),"badge-variant":"success",color:"gold",clickable:!0,onClick:a[1]||(a[1]=()=>{h.value.status="paid",m.value.current_page=1,y()})},{icon:i(()=>[e(t(d),{icon:"cil-check-circle"})]),_:1},8,["label","value","badge"]),e(R,{label:t(l)("invoices.unpaid"),value:j.value.unpaid,badge:t(l)("invoices.unpaid"),"badge-variant":"warning",color:"gold",clickable:!0,onClick:a[2]||(a[2]=()=>{h.value.status="sent",m.value.current_page=1,y()})},{icon:i(()=>[e(t(d),{icon:"cil-warning"})]),_:1},8,["label","value","badge"]),e(R,{label:t(l)("invoices.totalAmount"),value:xt(j.value.totalAmount),"badge-variant":"info",color:"gold"},{icon:i(()=>[e(t(d),{icon:"cil-money"})]),_:1},8,["label","value"])]),e(ht,{title:t(l)("common.filter"),icon:"cil-filter",class:"filters-card"},{default:i(()=>[e(t(L),{class:"g-3"},{default:i(()=>[e(t(x),{md:4},{default:i(()=>[e(t(Wt),{class:"search-input-group"},{default:i(()=>[e(t(Bt),{class:"search-icon-wrapper"},{default:i(()=>[e(t(d),{icon:"cil-magnifying-glass"})]),_:1}),e(t(D),{modelValue:h.value.search,"onUpdate:modelValue":a[3]||(a[3]=s=>h.value.search=s),placeholder:t(l)("common.search"),onInput:Ct,class:"filter-input search-input"},null,8,["modelValue","placeholder"])]),_:1})]),_:1}),e(t(x),{md:3},{default:i(()=>[e(t(W),{modelValue:h.value.status,"onUpdate:modelValue":a[4]||(a[4]=s=>h.value.status=s),onChange:y,class:"filter-select"},{default:i(()=>[o("option",Jt,r(t(l)("common.status")),1),a[13]||(a[13]=o("option",{value:"draft"},"Draft",-1)),a[14]||(a[14]=o("option",{value:"sent"},"Sent",-1)),o("option",te,r(t(l)("invoices.paid")),1),a[15]||(a[15]=o("option",{value:"partial"},"Partially Paid",-1)),a[16]||(a[16]=o("option",{value:"overdue"},"Overdue",-1))]),_:1},8,["modelValue"])]),_:1}),e(t(x),{md:2},{default:i(()=>[e(t(D),{modelValue:h.value.date_from,"onUpdate:modelValue":a[5]||(a[5]=s=>h.value.date_from=s),type:"date",label:t(l)("reports.fromDate"),onChange:y,class:"filter-input"},null,8,["modelValue","label"])]),_:1}),e(t(x),{md:2},{default:i(()=>[e(t(D),{modelValue:h.value.date_to,"onUpdate:modelValue":a[6]||(a[6]=s=>h.value.date_to=s),type:"date",label:t(l)("reports.toDate"),onChange:y,class:"filter-input"},null,8,["modelValue","label"])]),_:1}),e(t(x),{md:2},{default:i(()=>[e(t(C),{color:"secondary",variant:"outline",onClick:Vt,class:"w-100 reset-btn"},{default:i(()=>[e(t(d),{icon:"cil-reload",class:"me-1"}),f(" "+r(t(l)("common.reset")),1)]),_:1})]),_:1})]),_:1})]),_:1},8,["title"]),e(ht,{title:t(l)("invoices.title"),icon:"cil-list"},{footer:i(()=>[m.value.total_pages>1?(p(),_("div",qe,[o("div",Te,r(t(l)("common.view"))+" "+r((m.value.current_page-1)*m.value.per_page+1)+" "+r(t(l)("common.to"))+" "+r(Math.min(m.value.current_page*m.value.per_page,m.value.total))+" "+r(t(l)("common.of"))+" "+r(m.value.total),1),e(t(Ot),{pages:m.value.total_pages,"active-page":m.value.current_page,"onUpdate:activePage":$t},null,8,["pages","active-page"])])):U("",!0)]),default:i(()=>[G.value?(p(),B(yt,{key:0,text:t(l)("common.loading")},null,8,["text"])):V.value.length===0?(p(),B(Ht,{key:1,title:t(l)("invoices.noInvoices")||t(l)("common.noData"),description:t(l)("invoices.noInvoicesFound")||t(l)("invoices.title")+" - "+t(l)("common.noData"),"icon-color":"gray"},{action:i(()=>[e(t(C),{color:"primary",class:"btn-primary-custom",onClick:vt},{default:i(()=>[f(r(t(l)("invoices.title"))+" "+r(t(l)("common.new")),1)]),_:1})]),_:1},8,["title","description"])):(p(),_("div",ee,[e(t(bt),{hover:"",responsive:"",class:"table-modern invoices-table"},{default:i(()=>[o("thead",null,[o("tr",ae,[a[17]||(a[17]=o("th",{class:"th-id"},"#",-1)),o("th",oe,r(t(l)("invoices.invoiceNumber")),1),o("th",se,r(t(l)("invoices.customer")),1),o("th",le,r(t(l)("invoices.amount")),1),a[18]||(a[18]=o("th",{class:"th-paid"},"Paid",-1)),a[19]||(a[19]=o("th",{class:"th-due"},"Due",-1)),o("th",ie,r(t(l)("common.status")),1),o("th",ne,r(t(l)("invoices.date")),1),o("th",re,r(t(l)("common.actions")),1)])]),o("tbody",null,[(p(!0),_(O,null,Q(V.value,s=>(p(),_("tr",{key:s.id,class:st(["table-row invoice-row",{"highlight-row":Number(s.id)===Number(X.value)}]),"data-invoice-id":s.id},[o("td",de,[o("span",ue,"#"+r(s.id),1)]),o("td",me,[o("strong",pe,"#"+r(s.invoice_number||s.id),1)]),o("td",ve,[o("div",_e,[o("strong",fe,r(s.customer_name||"N/A"),1),s.customer_phone?(p(),_("a",{key:0,href:`tel:${s.customer_phone}`,class:"phone-link"},[e(t(d),{icon:"cil-phone",class:"phone-icon"}),o("span",null,r(s.customer_phone),1)],8,ge)):U("",!0)])]),o("td",be,[o("strong",he,[e(t(d),{icon:"cil-money",class:"money-icon"}),f(" "+r($(s.total||0)),1)])]),o("td",ye,[e(t(lt),{class:"unified-badge"},{default:i(()=>[e(t(d),{icon:"cil-check-circle",class:"badge-icon"}),o("span",null,r($(s.paid_amount||0)),1)]),_:2},1024)]),o("td",xe,[e(t(lt),{class:st(["unified-badge",Number(s.due_amount||0)>0?"due-badge":"paid-badge"])},{default:i(()=>[e(t(d),{icon:Number(s.due_amount||0)>0?"cil-warning":"cil-check-circle",class:"badge-icon"},null,8,["icon"]),o("span",null,r($(s.due_amount||0)),1)]),_:2},1032,["class"])]),o("td",we,[e(t(lt),{class:st(["unified-badge status-badge",`status-${s.status||"draft"}`])},{default:i(()=>[e(t(d),{icon:kt(s.status),class:"badge-icon"},null,8,["icon"]),o("span",null,r(wt(s.status)),1)]),_:2},1032,["class"])]),o("td",ke,[o("div",Ce,[e(t(d),{icon:"cil-calendar",class:"date-icon"}),o("span",null,r(dt(s.issue_date)),1)])]),o("td",$e,[o("div",Ve,[o("button",{class:"action-btn",onClick:u=>It(s),title:t(l)("common.view")},[e(t(d),{icon:"cil-info"})],8,De),o("button",{class:"action-btn",onClick:u=>pt(s),title:t(l)("invoices.print")},[e(t(d),{icon:"cil-print"})],8,Ne),s.status!=="paid"?(p(),_("button",{key:0,class:"action-btn",onClick:u=>St(s),title:t(l)("common.edit")},[e(t(d),{icon:"cil-pencil"})],8,Ie)):U("",!0),Number(s.due_amount||0)>0?(p(),_("button",{key:1,class:"action-btn",onClick:u=>Nt(s),title:t(l)("invoices.markPaid")},[e(t(d),{icon:"cil-check"})],8,Se)):U("",!0)])])],10,ce))),128))])]),_:1})]))]),_:1},8,["title"]),e(t(ot),{visible:Y.value,onClose:mt,size:"lg"},{default:i(()=>[e(t(it),null,{default:i(()=>[e(t(nt),null,{default:i(()=>{var s,u;return[e(t(d),{icon:"cil-file",class:"me-2"}),f(" "+r(t(l)("invoices.title"))+" - "+r(((s=g.value)==null?void 0:s.invoice_number)||((u=g.value)==null?void 0:u.id)||""),1)]}),_:1})]),_:1}),e(t(rt),null,{default:i(()=>[E.value?(p(),B(yt,{key:0,text:t(l)("common.loading")},null,8,["text"])):g.value?(p(),_("div",Ue,[o("div",Ae,[o("div",Fe,[e(t(d),{icon:"cil-user"})]),o("div",Pe,[o("h4",Me,r(g.value.customer_name||"N/A"),1),o("div",Ee,[o("span",ze,[e(t(d),{icon:"cil-calendar",class:"me-1"}),f(" "+r(dt(g.value.issue_date)),1)]),g.value.customer_phone?(p(),_("span",je,[e(t(d),{icon:"cil-phone",class:"me-1"}),f(" "+r(g.value.customer_phone),1)])):U("",!0)])]),o("div",Ke,[e(t(C),{color:"primary",class:"btn-primary-custom",onClick:a[7]||(a[7]=s=>pt(g.value))},{default:i(()=>[e(t(d),{icon:"cil-print",class:"me-2"}),f(" "+r(t(l)("invoices.print")),1)]),_:1})])]),o("div",Le,[o("div",We,[e(t(d),{icon:"cil-money",class:"stat-icon"}),o("div",Be,[o("div",Oe,r(t(l)("invoices.amount")),1),o("div",Qe,r($(g.value.total||0)),1)])]),o("div",Re,[e(t(d),{icon:"cil-check-circle",class:"stat-icon"}),o("div",Ge,[a[20]||(a[20]=o("div",{class:"stat-label"},"Paid",-1)),o("div",He,r($(g.value.paid_amount||0)),1)])]),o("div",Xe,[e(t(d),{icon:"cil-warning",class:"stat-icon"}),o("div",Ye,[a[21]||(a[21]=o("div",{class:"stat-label"},"Due",-1)),o("div",Ze,r($(g.value.due_amount||0)),1)])])]),o("div",Je,[a[23]||(a[23]=o("h6",{class:"items-title"},"Items",-1)),(g.value.items||[]).length===0?(p(),_("div",ta," - ")):(p(),B(t(bt),{key:1,responsive:"",class:"table-modern items-table"},{default:i(()=>[a[22]||(a[22]=o("thead",null,[o("tr",{class:"table-header-row"},[o("th",null,"Description"),o("th",{style:{width:"90px"}},"Qty"),o("th",{style:{width:"140px"}},"Unit"),o("th",{style:{width:"140px"}},"Total")])],-1)),o("tbody",null,[(p(!0),_(O,null,Q(g.value.items,s=>(p(),_("tr",{key:s.id},[o("td",null,r(s.description),1),o("td",null,r(s.quantity),1),o("td",null,r($(s.unit_price)),1),o("td",null,r($(s.total)),1)]))),128))])]),_:1}))])])):U("",!0)]),_:1}),e(t(ct),null,{default:i(()=>[e(t(C),{color:"secondary",onClick:mt},{default:i(()=>[f(r(t(l)("common.close")),1)]),_:1})]),_:1})]),_:1},8,["visible"]),e(t(ot),{visible:Z.value,onClose:tt,size:"lg"},{default:i(()=>[e(t(it),null,{default:i(()=>[e(t(nt),null,{default:i(()=>{var s,u;return[e(t(d),{icon:"cil-pencil",class:"me-2"}),f(" "+r(t(l)("common.edit"))+" - "+r(((s=S.value)==null?void 0:s.invoice_number)||((u=S.value)==null?void 0:u.id)||""),1)]}),_:1})]),_:1}),e(t(rt),null,{default:i(()=>[e(t(L),{class:"g-3"},{default:i(()=>[e(t(x),{md:6},{default:i(()=>[e(t(W),{modelValue:N.value.status,"onUpdate:modelValue":a[8]||(a[8]=s=>N.value.status=s),label:t(l)("common.status"),class:"filter-select"},{default:i(()=>[a[24]||(a[24]=o("option",{value:"draft"},"Draft",-1)),a[25]||(a[25]=o("option",{value:"sent"},"Sent",-1)),o("option",ea,r(t(l)("invoices.paid")),1),a[26]||(a[26]=o("option",{value:"partial"},"Partially Paid",-1)),a[27]||(a[27]=o("option",{value:"overdue"},"Overdue",-1))]),_:1},8,["modelValue","label"])]),_:1}),e(t(x),{md:6},{default:i(()=>[e(t(D),{modelValue:N.value.paid_amount,"onUpdate:modelValue":a[9]||(a[9]=s=>N.value.paid_amount=s),type:"number",step:"0.001",label:"Paid Amount",class:"filter-input"},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(t(ct),null,{default:i(()=>[e(t(C),{color:"secondary",onClick:tt},{default:i(()=>[f(r(t(l)("common.cancel")),1)]),_:1}),e(t(C),{color:"primary",class:"btn-primary-custom",disabled:z.value,onClick:qt},{default:i(()=>[e(t(d),{icon:"cil-save",class:"me-2"}),f(" "+r(t(l)("common.save")),1)]),_:1},8,["disabled"])]),_:1})]),_:1},8,["visible"]),e(t(ot),{visible:H.value,onClose:et,size:"lg"},{default:i(()=>[e(t(it),null,{default:i(()=>[e(t(nt),null,{default:i(()=>[e(t(d),{icon:"cil-plus",class:"me-2"}),f(" "+r(t(l)("invoices.title"))+" "+r(t(l)("common.new")),1)]),_:1})]),_:1}),e(t(rt),null,{default:i(()=>[e(t(L),{class:"g-3"},{default:i(()=>[e(t(x),{md:6},{default:i(()=>[e(t(W),{modelValue:v.value.customer_id,"onUpdate:modelValue":a[10]||(a[10]=s=>v.value.customer_id=s),label:t(l)("invoices.customer"),class:"filter-select"},{default:i(()=>[o("option",aa,r(t(l)("common.select")),1),(p(!0),_(O,null,Q(J.value,s=>(p(),_("option",{key:s.id,value:s.id},r(s.name)+" - "+r(s.phone),9,oa))),128))]),_:1},8,["modelValue","label"])]),_:1}),e(t(x),{md:3},{default:i(()=>[e(t(D),{modelValue:v.value.issue_date,"onUpdate:modelValue":a[11]||(a[11]=s=>v.value.issue_date=s),type:"date",label:t(l)("invoices.date"),class:"filter-input"},null,8,["modelValue","label"])]),_:1}),e(t(x),{md:3},{default:i(()=>[e(t(W),{modelValue:v.value.status,"onUpdate:modelValue":a[12]||(a[12]=s=>v.value.status=s),label:t(l)("common.status"),class:"filter-select"},{default:i(()=>[...a[28]||(a[28]=[o("option",{value:"draft"},"Draft",-1),o("option",{value:"sent"},"Sent",-1)])]),_:1},8,["modelValue","label"])]),_:1})]),_:1}),o("div",sa,[o("div",la,[a[30]||(a[30]=o("h6",{class:"m-0"},"Items",-1)),e(t(C),{color:"primary",variant:"outline",onClick:Ut},{default:i(()=>[e(t(d),{icon:"cil-plus",class:"me-1"}),a[29]||(a[29]=f(" Add Item ",-1))]),_:1})]),(p(!0),_(O,null,Q(v.value.items,(s,u)=>(p(),_("div",{key:u,class:"create-item-row"},[e(t(L),{class:"g-2 align-items-end"},{default:i(()=>[e(t(x),{md:6},{default:i(()=>[e(t(D),{modelValue:s.description,"onUpdate:modelValue":c=>s.description=c,label:"Description",class:"filter-input"},null,8,["modelValue","onUpdate:modelValue"])]),_:2},1024),e(t(x),{md:2},{default:i(()=>[e(t(D),{modelValue:s.quantity,"onUpdate:modelValue":c=>s.quantity=c,modelModifiers:{number:!0},type:"number",min:"1",step:"1",label:"Qty",class:"filter-input"},null,8,["modelValue","onUpdate:modelValue"])]),_:2},1024),e(t(x),{md:3},{default:i(()=>[e(t(D),{modelValue:s.unit_price,"onUpdate:modelValue":c=>s.unit_price=c,modelModifiers:{number:!0},type:"number",min:"0",step:"0.001",label:"Unit Price",class:"filter-input"},null,8,["modelValue","onUpdate:modelValue"])]),_:2},1024),e(t(x),{md:1,class:"d-flex"},{default:i(()=>[e(t(C),{color:"danger",variant:"outline",class:"w-100",onClick:c=>At(u)},{default:i(()=>[e(t(d),{icon:"cil-trash"})]),_:1},8,["onClick"])]),_:2},1024)]),_:2},1024)]))),128)),o("div",ia,[o("div",na,[a[31]||(a[31]=o("span",{class:"text-muted"},"Subtotal",-1)),o("strong",null,r($(at.value.subtotal)),1)]),o("div",ra,[a[32]||(a[32]=o("span",{class:"text-muted"},"Total",-1)),o("strong",null,r($(at.value.total)),1)])])])]),_:1}),e(t(ct),null,{default:i(()=>[e(t(C),{color:"secondary",onClick:et},{default:i(()=>[f(r(t(l)("common.cancel")),1)]),_:1}),e(t(C),{color:"primary",class:"btn-primary-custom",disabled:F.value,onClick:Ft},{default:i(()=>[e(t(d),{icon:"cil-save",class:"me-2"}),f(" "+r(t(l)("common.save")),1)]),_:1},8,["disabled"])]),_:1})]),_:1},8,["visible"])]))}},Va=Gt(ca,[["__scopeId","data-v-9bb71394"]]);export{Va as default};
