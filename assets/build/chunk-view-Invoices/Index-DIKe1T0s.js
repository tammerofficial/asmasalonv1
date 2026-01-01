import{f as b,g as gt,i as zt,l as jt,w as Lt,c as f,o as m,d as e,b as s,p as i,u as t,Z as st,L as Wt,x as k,P as v,C as d,t as r,_ as K,$ as x,a2 as Kt,a3 as Bt,a1 as D,a0 as B,s as E,a4 as bt,F as O,k as Q,n as ot,a as I,Y as lt,a6 as Ot,a7 as it,a8 as nt,a9 as rt,aa as ct}from"../chunk-vendor-vue-CVODRAc9.js";import{u as Qt,a as S}from"../chunk-component-NotificationsBell-DXvfEbaA.js";import{P as Rt}from"../chunk-component-PageHeader-DIMefZ9l.js";import{S as R}from"../chunk-component-StatCard-CUh-yMQt.js";import{_ as Gt,C as ht}from"../chunk-component-Card-DAbGoLxt.js";import{E as Ht}from"../chunk-component-EmptyState-B8o9iOHP.js";import{L as yt}from"../chunk-component-LoadingSpinner-DYzABkFM.js";import{a as Yt}from"../chunk-view-Bookings/BookingAppearance-h1g7uA93.js";import"../chunk-vendor-6xaPOP3o.js";import"../chunk-vendor-charts-C66rl4lP.js";import"../chunk-vendor-pinia-DSPKUyr1.js";import"../chunk-vendor-axios-B9ygI19o.js";import"../chunk-component-Sidebar-BCBXCjN-.js";import"../chunk-view-Bookings/BookingFormPreview-CRxQyI9V.js";const Zt={class:"invoices-page"},Jt={class:"stats-grid"},Xt={value:""},te={value:"paid"},ee={key:2,class:"table-wrapper"},ae={class:"table-header-row"},se={class:"th-invoice"},oe={class:"th-customer"},le={class:"th-amount"},ie={class:"th-status"},ne={class:"th-date"},re={class:"th-actions"},ce=["data-invoice-id"],de={class:"td-id"},ue={class:"invoice-id-badge"},me={class:"td-invoice"},pe={class:"d-flex align-items-center gap-2"},ve={class:"invoice-number"},_e={class:"td-customer"},fe={class:"invoice-customer-cell"},ge={class:"customer-name"},be=["href"],he={class:"td-amount"},ye={class:"unified-amount"},xe={class:"td-paid"},we={class:"td-due"},ke={class:"td-status"},Ce={class:"td-date"},$e={class:"date-cell"},Ve={class:"td-actions"},De={class:"actions-group"},Ne=["onClick","title"],Ie=["onClick","title"],Se=["onClick","title"],qe=["onClick","title"],Te={key:0,class:"d-flex justify-content-between align-items-center"},Ue={class:"text-muted"},Ae={key:1,class:"invoice-details-view"},Pe={class:"invoice-details-header"},Fe={class:"invoice-details-avatar"},Me={class:"invoice-details-info"},Ee={class:"invoice-customer-name"},ze={class:"invoice-meta"},je={class:"meta-item"},Le={key:0,class:"meta-item"},We={class:"invoice-details-actions"},Ke={class:"invoice-details-stats"},Be={class:"stat-item"},Oe={class:"stat-content"},Qe={class:"stat-label"},Re={class:"stat-value"},Ge={class:"stat-item"},He={class:"stat-content"},Ye={class:"stat-value"},Ze={class:"stat-item"},Je={class:"stat-content"},Xe={class:"stat-value"},ta={class:"invoice-items-card"},ea={key:0,class:"text-muted"},aa={value:"paid"},sa={value:""},oa=["value"],la={class:"create-items"},ia={class:"d-flex justify-content-between align-items-center mb-2 mt-4"},na={class:"totals-box"},ra={class:"totals-row"},ca={class:"totals-row"},da={__name:"Index",setup(ua){const{t:l}=Qt(),A=jt(),C=Yt(),V=b([]),G=b(!1),H=b(!1),Y=b(null),Z=b(!1),g=b(null),z=b(!1),J=b(!1),q=b(null),j=b(!1),P=b(!1),X=b([]),h=b({search:"",status:"",date_from:"",date_to:"",customer_id:""}),p=b({current_page:1,per_page:20,total:0,total_pages:0}),L=gt(()=>{const n=V.value.length,a=V.value.filter(c=>c.status==="paid").length,o=V.value.filter(c=>c.status!=="paid"&&c.status!=="draft").length,u=V.value.reduce((c,T)=>c+(parseFloat(T.total)||0),0);return{total:n,paid:a,unpaid:o,totalAmount:u}}),$=n=>new Intl.NumberFormat("en-KW",{style:"currency",currency:"KWD",minimumFractionDigits:3}).format(n||0),xt=n=>{if(!n&&n!==0)return"0 KWD";const a=parseFloat(n);return a>=1e3?`${(a/1e3).toFixed(1)}K KWD`:`${a.toFixed(0)} KWD`},dt=n=>n?new Date(n).toLocaleDateString("en-US",{year:"numeric",month:"short",day:"numeric"}):"-",wt=n=>({draft:"Draft",sent:"Sent",paid:l("invoices.paid"),partial:"Partially Paid",overdue:"Overdue",cancelled:"Cancelled"})[n]||n,kt=n=>({draft:"cil-file",sent:"cil-paper-plane",paid:"cil-check-circle",partial:"cil-clock",overdue:"cil-warning",cancelled:"cil-x-circle"})[n]||"cil-info";let ut;const Ct=()=>{clearTimeout(ut),ut=setTimeout(()=>{p.value.current_page=1,y()},500)},y=async()=>{var n;G.value=!0;try{const a={page:p.value.current_page,per_page:p.value.per_page,...h.value};Object.keys(a).forEach(c=>{a[c]===""&&delete a[c]});const o=await S.get("/invoices",{params:a,noCache:!0}),u=((n=o.data)==null?void 0:n.data)||o.data||{};if(V.value=u.items||[],p.value=u.pagination||p.value,A.query.invoice_id){Y.value=Number(A.query.invoice_id),await Wt();const c=document.querySelector(`[data-invoice-id="${Y.value}"]`);c&&c.scrollIntoView({behavior:"smooth",block:"center"})}}catch(a){console.error("Error loading invoices:",a),C.error(l("common.errorLoading")),V.value=[],p.value={current_page:1,per_page:20,total:0,total_pages:0}}finally{G.value=!1}},$t=n=>{p.value.current_page=n,y()},Vt=()=>{h.value={search:"",status:"",date_from:"",date_to:"",customer_id:""},p.value.current_page=1,y()},Dt=()=>{console.log("Exporting invoices data..."),alert(l("common.export")+" - "+l("invoices.title"))},Nt=async n=>{if(confirm(`${l("invoices.markPaid")}: ${n.invoice_number||n.id}?`))try{await S.put(`/invoices/${n.id}`,{paid_amount:Number(n.total||0)}),C.success(l("invoices.paid")),clearCache("/invoices"),clearCache("/payments"),y()}catch(a){console.error("Error marking invoice as paid:",a),C.error(l("common.errorLoading"))}},mt=()=>{Z.value=!1,g.value=null,z.value=!1},It=async n=>{var a;Z.value=!0,z.value=!0,g.value={...n,items:[]};try{const o=await S.get(`/invoices/${n.id}`,{noCache:!0}),u=((a=o.data)==null?void 0:a.data)||o.data||{};g.value={...n,...u,customer_name:u.customer_name||n.customer_name,customer_phone:u.customer_phone||n.customer_phone,items:Array.isArray(u.items)?u.items:[]}}catch(o){console.error("Error loading invoice details:",o),C.error(l("common.errorLoading"))}finally{z.value=!1}},N=b({status:"sent",paid_amount:0}),St=n=>{q.value=n,N.value={status:n.status||"sent",paid_amount:Number(n.paid_amount||0)},J.value=!0},tt=()=>{J.value=!1,q.value=null,j.value=!1},qt=async()=>{if(q.value){j.value=!0;try{await S.put(`/invoices/${q.value.id}`,{status:N.value.status,paid_amount:Number(N.value.paid_amount||0)}),C.success(l("common.save")),clearCache("/invoices"),clearCache("/payments"),tt(),y()}catch(n){console.error("Save invoice error:",n),C.error(l("common.errorLoading"))}finally{j.value=!1}}},pt=async n=>{var a,o;try{const u=await S.get(`/invoices/${n.id}`,{noCache:!0}),c=((a=u.data)==null?void 0:a.data)||u.data||{},T=Array.isArray(c.items)?c.items:[],W=n.customer_name||"Customer",_t=c.invoice_number||n.invoice_number||n.id,Ft=c.issue_date||n.issue_date||"",ft=(typeof AsmaaSalonConfig<"u"&&AsmaaSalonConfig?AsmaaSalonConfig:{}).logoUrl||"https://asmaaljarallah.com/wp-content/uploads/2025/03/logoDark.png",Mt="Asmaaljarallah",F=(((o=document==null?void 0:document.documentElement)==null?void 0:o.getAttribute("dir"))||"ltr")==="rtl",w=F?{invoice:"فاتورة",invoiceNo:"رقم الفاتورة",date:"التاريخ",customer:"العميلة",description:"الوصف",qty:"الكمية",unit:"السعر",total:"الإجمالي",subtotal:"المجموع",discount:"الخصم",grandTotal:"الإجمالي النهائي",thanks:"شكراً لزيارتكم 💛"}:{invoice:"Invoice",invoiceNo:"Invoice No",date:"Date",customer:"Customer",description:"Description",qty:"Qty",unit:"Unit",total:"Total",subtotal:"Subtotal",discount:"Discount",grandTotal:"Total",thanks:"Thank you for your visit"},Et=`
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
              ${F?"direction: rtl;":"direction: ltr;"}
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
              text-align: ${F?"right":"left"};
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
            .num { text-align: ${F?"left":"right"}; white-space: nowrap; }
            .desc { color: #111; font-weight: 700; }
            .totals {
              margin-top: 14px;
              display: flex;
              justify-content: ${F?"flex-start":"flex-end"};
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
                <div class="meta-row"><span class="k">${w.date}</span><span class="v">${Ft}</span></div>
                <div class="meta-row"><span class="k">${w.customer}</span><span class="v">${W}</span></div>
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
              ${T.map(M=>`
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
    `,U=window.open("","_blank");if(!U){C.error("Popup blocked");return}U.document.open(),U.document.write(Et),U.document.close(),setTimeout(()=>{try{U.focus(),U.print()}catch{}},250)}catch(u){console.error("Print invoice error:",u),C.error(l("common.errorLoading"))}},et=()=>{H.value=!1,P.value=!1},vt=async()=>{H.value=!0,await Tt()},Tt=async()=>{var n;if(!(X.value.length>0))try{const a=await S.get("/customers",{params:{per_page:100},noCache:!0}),o=((n=a.data)==null?void 0:n.data)||a.data||{};X.value=o.items||[]}catch(a){console.error("Error loading customers for invoice:",a)}},_=b({customer_id:"",issue_date:new Date().toISOString().slice(0,10),status:"draft",items:[{description:"",quantity:1,unit_price:0}]}),at=gt(()=>{const n=(_.value.items||[]).reduce((a,o)=>{const u=Number(o.quantity||0),c=Number(o.unit_price||0);return a+u*c},0);return{subtotal:n,total:n}}),Ut=()=>{_.value.items.push({description:"",quantity:1,unit_price:0})},At=n=>{_.value.items.length<=1||_.value.items.splice(n,1)},Pt=async()=>{if(!_.value.customer_id){C.error(`${l("invoices.customer")} is required`);return}P.value=!0;try{const n=at.value.subtotal;if(n<=0){C.error("Total must be greater than 0"),P.value=!1;return}const a=_.value.items.map(c=>{const T=Number(c.quantity||0),W=Number(c.unit_price||0);return{description:(c.description||"").toString(),quantity:T,unit_price:W,total:T*W}}).filter(c=>c.description||c.total>0),o={customer_id:Number(_.value.customer_id),issue_date:_.value.issue_date,status:_.value.status,subtotal:n,discount:0,tax:0,total:n,items:a},u=await S.post("/invoices",o);C.success(l("common.save")),clearCache("/invoices"),clearCache("/payments"),et(),_.value={customer_id:"",issue_date:new Date().toISOString().slice(0,10),status:"draft",items:[{description:"",quantity:1,unit_price:0}]},y()}catch(n){console.error("Create invoice error:",n),C.error(l("common.errorLoading"))}finally{P.value=!1}};return zt(()=>{A.query.customer_id&&(h.value.customer_id=String(A.query.customer_id)),y()}),Lt(()=>A.query.customer_id,n=>{n&&(h.value.customer_id=String(n),p.value.current_page=1,y())}),(n,a)=>(m(),f("div",Zt,[e(Rt,{title:t(l)("invoices.title"),subtitle:t(l)("invoices.subtitle")||t(l)("invoices.title")+" - "+t(l)("dashboard.subtitle")},{icon:i(()=>[e(t(d),{icon:"cil-file"})]),actions:i(()=>[e(t(k),{color:"secondary",variant:"outline",onClick:Dt,class:"me-2 export-btn"},{default:i(()=>[e(t(d),{icon:"cil-cloud-download",class:"me-2"}),v(" "+r(t(l)("common.export")),1)]),_:1}),e(t(k),{color:"primary",class:"btn-primary-custom",onClick:vt},{default:i(()=>[e(t(d),{icon:"cil-plus",class:"me-2"}),v(" "+r(t(l)("invoices.title"))+" "+r(t(l)("common.new")),1)]),_:1})]),_:1},8,["title","subtitle"]),s("div",Jt,[e(R,{label:t(l)("invoices.title"),value:L.value.total||V.value.length,"badge-variant":"info",color:"gold",clickable:!0,onClick:a[0]||(a[0]=()=>{h.value.status="",p.value.current_page=1,y()})},{icon:i(()=>[e(t(d),{icon:"cil-file"})]),_:1},8,["label","value"]),e(R,{label:t(l)("invoices.paid"),value:L.value.paid,badge:t(l)("invoices.paid"),"badge-variant":"success",color:"gold",clickable:!0,onClick:a[1]||(a[1]=()=>{h.value.status="paid",p.value.current_page=1,y()})},{icon:i(()=>[e(t(d),{icon:"cil-check-circle"})]),_:1},8,["label","value","badge"]),e(R,{label:t(l)("invoices.unpaid"),value:L.value.unpaid,badge:t(l)("invoices.unpaid"),"badge-variant":"warning",color:"gold",clickable:!0,onClick:a[2]||(a[2]=()=>{h.value.status="sent",p.value.current_page=1,y()})},{icon:i(()=>[e(t(d),{icon:"cil-warning"})]),_:1},8,["label","value","badge"]),e(R,{label:t(l)("invoices.totalAmount"),value:xt(L.value.totalAmount),"badge-variant":"info",color:"gold"},{icon:i(()=>[e(t(d),{icon:"cil-money"})]),_:1},8,["label","value"])]),e(ht,{title:t(l)("common.filter"),icon:"cil-filter",class:"filters-card"},{default:i(()=>[e(t(K),{class:"g-3"},{default:i(()=>[e(t(x),{md:4},{default:i(()=>[e(t(Kt),{class:"search-input-group"},{default:i(()=>[e(t(Bt),{class:"search-icon-wrapper"},{default:i(()=>[e(t(d),{icon:"cil-magnifying-glass"})]),_:1}),e(t(D),{modelValue:h.value.search,"onUpdate:modelValue":a[3]||(a[3]=o=>h.value.search=o),placeholder:t(l)("common.search"),onInput:Ct,class:"filter-input search-input"},null,8,["modelValue","placeholder"])]),_:1})]),_:1}),e(t(x),{md:3},{default:i(()=>[e(t(B),{modelValue:h.value.status,"onUpdate:modelValue":a[4]||(a[4]=o=>h.value.status=o),onChange:y,class:"filter-select"},{default:i(()=>[s("option",Xt,r(t(l)("common.status")),1),a[13]||(a[13]=s("option",{value:"draft"},"Draft",-1)),a[14]||(a[14]=s("option",{value:"sent"},"Sent",-1)),s("option",te,r(t(l)("invoices.paid")),1),a[15]||(a[15]=s("option",{value:"partial"},"Partially Paid",-1)),a[16]||(a[16]=s("option",{value:"overdue"},"Overdue",-1))]),_:1},8,["modelValue"])]),_:1}),e(t(x),{md:2},{default:i(()=>[e(t(D),{modelValue:h.value.date_from,"onUpdate:modelValue":a[5]||(a[5]=o=>h.value.date_from=o),type:"date",label:t(l)("reports.fromDate"),onChange:y,class:"filter-input"},null,8,["modelValue","label"])]),_:1}),e(t(x),{md:2},{default:i(()=>[e(t(D),{modelValue:h.value.date_to,"onUpdate:modelValue":a[6]||(a[6]=o=>h.value.date_to=o),type:"date",label:t(l)("reports.toDate"),onChange:y,class:"filter-input"},null,8,["modelValue","label"])]),_:1}),e(t(x),{md:2},{default:i(()=>[e(t(k),{color:"secondary",variant:"outline",onClick:Vt,class:"w-100 reset-btn"},{default:i(()=>[e(t(d),{icon:"cil-reload",class:"me-1"}),v(" "+r(t(l)("common.reset")),1)]),_:1})]),_:1})]),_:1})]),_:1},8,["title"]),e(ht,{title:t(l)("invoices.title"),icon:"cil-list"},{footer:i(()=>[p.value.total_pages>1?(m(),f("div",Te,[s("div",Ue,r(t(l)("common.view"))+" "+r((p.value.current_page-1)*p.value.per_page+1)+" "+r(t(l)("common.to"))+" "+r(Math.min(p.value.current_page*p.value.per_page,p.value.total))+" "+r(t(l)("common.of"))+" "+r(p.value.total),1),e(t(Ot),{pages:p.value.total_pages,"active-page":p.value.current_page,"onUpdate:activePage":$t},null,8,["pages","active-page"])])):I("",!0)]),default:i(()=>[G.value?(m(),E(yt,{key:0,text:t(l)("common.loading")},null,8,["text"])):V.value.length===0?(m(),E(Ht,{key:1,title:t(l)("invoices.noInvoices")||t(l)("common.noData"),description:t(l)("invoices.noInvoicesFound")||t(l)("invoices.title")+" - "+t(l)("common.noData"),"icon-color":"gray"},{action:i(()=>[e(t(k),{color:"primary",class:"btn-primary-custom",onClick:vt},{default:i(()=>[v(r(t(l)("invoices.title"))+" "+r(t(l)("common.new")),1)]),_:1})]),_:1},8,["title","description"])):(m(),f("div",ee,[e(t(bt),{hover:"",responsive:"",class:"table-modern invoices-table"},{default:i(()=>[s("thead",null,[s("tr",ae,[a[17]||(a[17]=s("th",{class:"th-id"},"#",-1)),s("th",se,r(t(l)("invoices.invoiceNumber")),1),s("th",oe,r(t(l)("invoices.customer")),1),s("th",le,r(t(l)("invoices.amount")),1),a[18]||(a[18]=s("th",{class:"th-paid"},"Paid",-1)),a[19]||(a[19]=s("th",{class:"th-due"},"Due",-1)),s("th",ie,r(t(l)("common.status")),1),s("th",ne,r(t(l)("invoices.date")),1),s("th",re,r(t(l)("common.actions")),1)])]),s("tbody",null,[(m(!0),f(O,null,Q(V.value,o=>(m(),f("tr",{key:o.id,class:ot(["table-row invoice-row",{"highlight-row":Number(o.id)===Number(Y.value)}]),"data-invoice-id":o.id},[s("td",de,[s("span",ue,"#"+r(o.id),1)]),s("td",me,[s("div",pe,[s("strong",ve,"#"+r(o.invoice_number||o.id),1),o.wc_order_id?(m(),E(t(k),{key:0,color:"success",size:"sm",variant:"outline",href:`/wp-admin/post.php?post=${o.wc_order_id}&action=edit`,target:"_blank",title:"WooCommerce Order #"+o.wc_order_id,class:"wc-link-btn"},{default:i(()=>[e(t(d),{icon:"cil-cart",class:"me-1"}),a[20]||(a[20]=v(" WC ",-1))]),_:1},8,["href","title"])):I("",!0)])]),s("td",_e,[s("div",fe,[s("strong",ge,r(o.customer_name||"N/A"),1),o.customer_phone?(m(),f("a",{key:0,href:`tel:${o.customer_phone}`,class:"phone-link"},[e(t(d),{icon:"cil-phone",class:"phone-icon"}),s("span",null,r(o.customer_phone),1)],8,be)):I("",!0)])]),s("td",he,[s("strong",ye,[e(t(d),{icon:"cil-money",class:"money-icon"}),v(" "+r($(o.total||0)),1)])]),s("td",xe,[e(t(lt),{class:"unified-badge"},{default:i(()=>[e(t(d),{icon:"cil-check-circle",class:"badge-icon"}),s("span",null,r($(o.paid_amount||0)),1)]),_:2},1024)]),s("td",we,[e(t(lt),{class:ot(["unified-badge",Number(o.due_amount||0)>0?"due-badge":"paid-badge"])},{default:i(()=>[e(t(d),{icon:Number(o.due_amount||0)>0?"cil-warning":"cil-check-circle",class:"badge-icon"},null,8,["icon"]),s("span",null,r($(o.due_amount||0)),1)]),_:2},1032,["class"])]),s("td",ke,[e(t(lt),{class:ot(["unified-badge status-badge",`status-${o.status||"draft"}`])},{default:i(()=>[e(t(d),{icon:kt(o.status),class:"badge-icon"},null,8,["icon"]),s("span",null,r(wt(o.status)),1)]),_:2},1032,["class"])]),s("td",Ce,[s("div",$e,[e(t(d),{icon:"cil-calendar",class:"date-icon"}),s("span",null,r(dt(o.issue_date)),1)])]),s("td",Ve,[s("div",De,[s("button",{class:"action-btn",onClick:u=>It(o),title:t(l)("common.view")},[e(t(d),{icon:"cil-info"})],8,Ne),s("button",{class:"action-btn",onClick:u=>pt(o),title:t(l)("invoices.print")},[e(t(d),{icon:"cil-print"})],8,Ie),o.status!=="paid"?(m(),f("button",{key:0,class:"action-btn",onClick:u=>St(o),title:t(l)("common.edit")},[e(t(d),{icon:"cil-pencil"})],8,Se)):I("",!0),Number(o.due_amount||0)>0?(m(),f("button",{key:1,class:"action-btn",onClick:u=>Nt(o),title:t(l)("invoices.markPaid")},[e(t(d),{icon:"cil-check"})],8,qe)):I("",!0)])])],10,ce))),128))])]),_:1})]))]),_:1},8,["title"]),e(t(st),{visible:Z.value,onClose:mt,size:"lg"},{default:i(()=>[e(t(it),null,{default:i(()=>[e(t(nt),null,{default:i(()=>{var o,u;return[e(t(d),{icon:"cil-file",class:"me-2"}),v(" "+r(t(l)("invoices.title"))+" - "+r(((o=g.value)==null?void 0:o.invoice_number)||((u=g.value)==null?void 0:u.id)||""),1)]}),_:1})]),_:1}),e(t(rt),null,{default:i(()=>[z.value?(m(),E(yt,{key:0,text:t(l)("common.loading")},null,8,["text"])):g.value?(m(),f("div",Ae,[s("div",Pe,[s("div",Fe,[e(t(d),{icon:"cil-user"})]),s("div",Me,[s("h4",Ee,r(g.value.customer_name||"N/A"),1),s("div",ze,[s("span",je,[e(t(d),{icon:"cil-calendar",class:"me-1"}),v(" "+r(dt(g.value.issue_date)),1)]),g.value.customer_phone?(m(),f("span",Le,[e(t(d),{icon:"cil-phone",class:"me-1"}),v(" "+r(g.value.customer_phone),1)])):I("",!0)])]),s("div",We,[e(t(k),{color:"primary",class:"btn-primary-custom",onClick:a[7]||(a[7]=o=>pt(g.value))},{default:i(()=>[e(t(d),{icon:"cil-print",class:"me-2"}),v(" "+r(t(l)("invoices.print")),1)]),_:1})])]),s("div",Ke,[s("div",Be,[e(t(d),{icon:"cil-money",class:"stat-icon"}),s("div",Oe,[s("div",Qe,r(t(l)("invoices.amount")),1),s("div",Re,r($(g.value.total||0)),1)])]),s("div",Ge,[e(t(d),{icon:"cil-check-circle",class:"stat-icon"}),s("div",He,[a[21]||(a[21]=s("div",{class:"stat-label"},"Paid",-1)),s("div",Ye,r($(g.value.paid_amount||0)),1)])]),s("div",Ze,[e(t(d),{icon:"cil-warning",class:"stat-icon"}),s("div",Je,[a[22]||(a[22]=s("div",{class:"stat-label"},"Due",-1)),s("div",Xe,r($(g.value.due_amount||0)),1)])])]),s("div",ta,[a[24]||(a[24]=s("h6",{class:"items-title"},"Items",-1)),(g.value.items||[]).length===0?(m(),f("div",ea," - ")):(m(),E(t(bt),{key:1,responsive:"",class:"table-modern items-table"},{default:i(()=>[a[23]||(a[23]=s("thead",null,[s("tr",{class:"table-header-row"},[s("th",null,"Description"),s("th",{style:{width:"90px"}},"Qty"),s("th",{style:{width:"140px"}},"Unit"),s("th",{style:{width:"140px"}},"Total")])],-1)),s("tbody",null,[(m(!0),f(O,null,Q(g.value.items,o=>(m(),f("tr",{key:o.id},[s("td",null,r(o.description),1),s("td",null,r(o.quantity),1),s("td",null,r($(o.unit_price)),1),s("td",null,r($(o.total)),1)]))),128))])]),_:1}))])])):I("",!0)]),_:1}),e(t(ct),null,{default:i(()=>[e(t(k),{color:"secondary",onClick:mt},{default:i(()=>[v(r(t(l)("common.close")),1)]),_:1})]),_:1})]),_:1},8,["visible"]),e(t(st),{visible:J.value,onClose:tt,size:"lg"},{default:i(()=>[e(t(it),null,{default:i(()=>[e(t(nt),null,{default:i(()=>{var o,u;return[e(t(d),{icon:"cil-pencil",class:"me-2"}),v(" "+r(t(l)("common.edit"))+" - "+r(((o=q.value)==null?void 0:o.invoice_number)||((u=q.value)==null?void 0:u.id)||""),1)]}),_:1})]),_:1}),e(t(rt),null,{default:i(()=>[e(t(K),{class:"g-3"},{default:i(()=>[e(t(x),{md:6},{default:i(()=>[e(t(B),{modelValue:N.value.status,"onUpdate:modelValue":a[8]||(a[8]=o=>N.value.status=o),label:t(l)("common.status"),class:"filter-select"},{default:i(()=>[a[25]||(a[25]=s("option",{value:"draft"},"Draft",-1)),a[26]||(a[26]=s("option",{value:"sent"},"Sent",-1)),s("option",aa,r(t(l)("invoices.paid")),1),a[27]||(a[27]=s("option",{value:"partial"},"Partially Paid",-1)),a[28]||(a[28]=s("option",{value:"overdue"},"Overdue",-1))]),_:1},8,["modelValue","label"])]),_:1}),e(t(x),{md:6},{default:i(()=>[e(t(D),{modelValue:N.value.paid_amount,"onUpdate:modelValue":a[9]||(a[9]=o=>N.value.paid_amount=o),type:"number",step:"0.001",label:"Paid Amount",class:"filter-input"},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(t(ct),null,{default:i(()=>[e(t(k),{color:"secondary",onClick:tt},{default:i(()=>[v(r(t(l)("common.cancel")),1)]),_:1}),e(t(k),{color:"primary",class:"btn-primary-custom",disabled:j.value,onClick:qt},{default:i(()=>[e(t(d),{icon:"cil-save",class:"me-2"}),v(" "+r(t(l)("common.save")),1)]),_:1},8,["disabled"])]),_:1})]),_:1},8,["visible"]),e(t(st),{visible:H.value,onClose:et,size:"lg"},{default:i(()=>[e(t(it),null,{default:i(()=>[e(t(nt),null,{default:i(()=>[e(t(d),{icon:"cil-plus",class:"me-2"}),v(" "+r(t(l)("invoices.title"))+" "+r(t(l)("common.new")),1)]),_:1})]),_:1}),e(t(rt),null,{default:i(()=>[e(t(K),{class:"g-3"},{default:i(()=>[e(t(x),{md:6},{default:i(()=>[e(t(B),{modelValue:_.value.customer_id,"onUpdate:modelValue":a[10]||(a[10]=o=>_.value.customer_id=o),label:t(l)("invoices.customer"),class:"filter-select"},{default:i(()=>[s("option",sa,r(t(l)("common.select")),1),(m(!0),f(O,null,Q(X.value,o=>(m(),f("option",{key:o.id,value:o.id},r(o.name)+" - "+r(o.phone),9,oa))),128))]),_:1},8,["modelValue","label"])]),_:1}),e(t(x),{md:3},{default:i(()=>[e(t(D),{modelValue:_.value.issue_date,"onUpdate:modelValue":a[11]||(a[11]=o=>_.value.issue_date=o),type:"date",label:t(l)("invoices.date"),class:"filter-input"},null,8,["modelValue","label"])]),_:1}),e(t(x),{md:3},{default:i(()=>[e(t(B),{modelValue:_.value.status,"onUpdate:modelValue":a[12]||(a[12]=o=>_.value.status=o),label:t(l)("common.status"),class:"filter-select"},{default:i(()=>[...a[29]||(a[29]=[s("option",{value:"draft"},"Draft",-1),s("option",{value:"sent"},"Sent",-1)])]),_:1},8,["modelValue","label"])]),_:1})]),_:1}),s("div",la,[s("div",ia,[a[31]||(a[31]=s("h6",{class:"m-0"},"Items",-1)),e(t(k),{color:"primary",variant:"outline",onClick:Ut},{default:i(()=>[e(t(d),{icon:"cil-plus",class:"me-1"}),a[30]||(a[30]=v(" Add Item ",-1))]),_:1})]),(m(!0),f(O,null,Q(_.value.items,(o,u)=>(m(),f("div",{key:u,class:"create-item-row"},[e(t(K),{class:"g-2 align-items-end"},{default:i(()=>[e(t(x),{md:6},{default:i(()=>[e(t(D),{modelValue:o.description,"onUpdate:modelValue":c=>o.description=c,label:"Description",class:"filter-input"},null,8,["modelValue","onUpdate:modelValue"])]),_:2},1024),e(t(x),{md:2},{default:i(()=>[e(t(D),{modelValue:o.quantity,"onUpdate:modelValue":c=>o.quantity=c,modelModifiers:{number:!0},type:"number",min:"1",step:"1",label:"Qty",class:"filter-input"},null,8,["modelValue","onUpdate:modelValue"])]),_:2},1024),e(t(x),{md:3},{default:i(()=>[e(t(D),{modelValue:o.unit_price,"onUpdate:modelValue":c=>o.unit_price=c,modelModifiers:{number:!0},type:"number",min:"0",step:"0.001",label:"Unit Price",class:"filter-input"},null,8,["modelValue","onUpdate:modelValue"])]),_:2},1024),e(t(x),{md:1,class:"d-flex"},{default:i(()=>[e(t(k),{color:"danger",variant:"outline",class:"w-100",onClick:c=>At(u)},{default:i(()=>[e(t(d),{icon:"cil-trash"})]),_:1},8,["onClick"])]),_:2},1024)]),_:2},1024)]))),128)),s("div",na,[s("div",ra,[a[32]||(a[32]=s("span",{class:"text-muted"},"Subtotal",-1)),s("strong",null,r($(at.value.subtotal)),1)]),s("div",ca,[a[33]||(a[33]=s("span",{class:"text-muted"},"Total",-1)),s("strong",null,r($(at.value.total)),1)])])])]),_:1}),e(t(ct),null,{default:i(()=>[e(t(k),{color:"secondary",onClick:et},{default:i(()=>[v(r(t(l)("common.cancel")),1)]),_:1}),e(t(k),{color:"primary",class:"btn-primary-custom",disabled:P.value,onClick:Pt},{default:i(()=>[e(t(d),{icon:"cil-save",class:"me-2"}),v(" "+r(t(l)("common.save")),1)]),_:1},8,["disabled"])]),_:1})]),_:1},8,["visible"])]))}},Da=Gt(da,[["__scopeId","data-v-5f9795b9"]]);export{Da as default};
