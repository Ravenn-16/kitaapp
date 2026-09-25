// Browser-only presentation data. No accounts or inventory are written to the database.
(() => {
  const longName = 'Responsive test product with a long descriptive name and packaging information';
  const user = {id:1,name:'Alexandra Responsive Test Account With A Long Name',email:'responsive.test@example.com',role:'manager',status:'Active',employeeId:'EMP-TEST-001',dateCreated:'2026-09-01'};
  const categories = [{name:'Household and everyday essentials',status:'Active',classification:'Non-Perishable'}];
  const products = Array.from({length:24},(_,i)=>({id:i+1,name:longName+' '+(i+1),category:categories[0].name,status:'Active',supplierId:1,price:125.50,costPrice:100,retailPrice:125.50,stock:8,minStock:10,unit:'Piece',purchaseUnit:'Box',stockUnit:'Piece',conversionFactor:12,barcode:'480123456789'+i,sku:'SKU-'+i,vatClass:'VATable',expiry:'2026-10-01',batch:'BATCH-TEST',lot:'LOT-TEST'}));
  const suppliers = [{id:1,name:'Responsive Test Supplier With A Long Registered Business Name',status:'Active',address:'Long business address for responsive layout verification',contact:user.name,phone:'09123456789',email:user.email,paymentTerms:'Cash on Delivery',categories:[categories[0].name],products:products.map(p=>({productId:p.id,costPrice:p.costPrice})),notes:'Supplier notes'}];
  const lines=products.slice(0,3).map(p=>({...p,productId:p.id,qty:12,confirmedQty:12,orderedQty:12,deliveredQty:5,unitCost:100,lineTotal:1200,poQty:12}));
  const receipt={id:'RCV-RESPONSIVE-01234567890123456789',date:'2026-09-25',received_at:'2026-09-25 10:00:00',receivedBy:user.name,deliveryReference:'SUPPLIER-DELIVERY-REFERENCE-0123456789',deliveryStatus:'Partially Received',lines};
  const requests=['Pending Approval','Approved','Declined'].map((status,i)=>({id:'PO-RESPONSIVE-0123456789012345678'+i,status,lines,supplierId:1,supplierName:suppliers[0].name,dateRequested:'2026-09-24',requested_at:'2026-09-24 09:00:00',approved_at:'2026-09-25 09:00:00',requestedBy:user.name,approvedBy:user.name,notes:'Purchase notes containing a long reference: '+'REFERENCE'.repeat(12),adminNote:'Review notes',poId:i===1?'PO-RESPONSIVE-01234567890123456781':null}));
  const order={...requests[1],status:'Partially Received',request:requests[1],created:'2026-09-24',lines,receipts:[receipt],orderedValue:3600,receivingVersion:1};
  const transaction={uuid:'TXN-RESPONSIVE-01234567890123456789',date:'2026-09-25',time:'10:15',cashier:user.name,cashierId:1,status:'Paid',paymentMode:'Cash',total:376.50,subtotal:376.50,discount:0,vat:40.34,tendered:500,change:123.50,lines:lines.map(l=>({...l,qty:1,unitPrice:125.50,lineTotal:125.50,refundedQty:0}))};
  const data={BUSINESS_DATE:'2026-09-25',PRODUCTS:products,CATEGORIES:categories,SUPPLIERS:suppliers,ITEM_REQUESTS:requests,PURCHASE_ORDERS:[order],RECEIVING_RECORDS:[receipt],ADJUSTMENTS:[],PROMOTIONS:[],USERS:{cashier:[{...user,role:'cashier'}],manager:[user],admin:[{...user,role:'admin'}],superadmin:[{...user,role:'superadmin'}]},AUDIT_LOGS:[{ts:'2026-09-25 10:00:00',user:user.name,action:'Updated',record:order.id,before:'Pending',after:'Approved'}],FIELD_VERSION_HISTORY:[{field:'name',record:products[0].sku,oldValue:'Old product',newValue:longName,user:user.name,ts:'2026-09-25',reason:'Name update'}],NOTIFICATIONS:[],SALES_LOG:products.map(p=>({date:'2026-09-25',hour:10,productId:p.id,qty:2,amount:p.price})),TRANSACTIONS:[transaction],BATCH_RECALL:[]};
  const saDashboard={total_users:120,active_users:110,inactive_users:10,average_daily_events:200,application_status:'Healthy',database_status:'Healthy',database_response_ms:1.2,generation_ms:3.5,measured_at:'2026-09-25T10:00:00Z',activity_days:Array.from({length:7},(_,i)=>({date:'2026-09-'+(18+i),events:100+i*20})),recent_activity:data.AUDIT_LOGS};
  transaction.cart=transaction.lines;
  data.BATCH_RECALL={batch:'BATCH-TEST',lot:'LOT-TEST',product:longName,affected:[{location:'Main warehouse receiving area',qty:20}]};
  suppliers[0].callbackLogs=[];
  transaction.totals={subtotal:376.50,discount:0,grandTotal:376.50,vatExempt:0};
  window.__fixture={data,user,products,suppliers,requests,order,receipt,transaction,saDashboard};
  const app=window.__app;
  clearInterval(app.notificationTimer); clearInterval(app.purchaseRefreshTimer);
  window.__initialState={...app.state};
  window.__showScreen=(screen,role,extra={})=>new Promise(resolve=>{
    const next={...window.__initialState,authStep:'in',role,screen,authenticatedUser:{...user,role},data,productsLocal:products,categoriesLocal:categories,suppliersLocal:suppliers,itemRequestsLocal:requests,purchaseOrdersLocal:[order],receivingLocal:[receipt],adjustmentsLocal:[],notificationsLocal:[],promotionsLocal:[],fieldVersionHistoryLocal:data.FIELD_VERSION_HISTORY,cashiersLocal:data.USERS.cashier,accountsLocal:Object.values(data.USERS).flat(),rolesMatrix:app.defaultRolesMatrix(),purchaseData:{requests,orders:[order]},saDashboard,compactNavigation:innerWidth<1024,...extra};
    // Check builders synchronously so fixture errors cannot leave a pending render callback.
    const previous=app.state;app.state=next;
    try{app.renderVals();}finally{app.state=previous;}
    app.setState(next,()=>requestAnimationFrame(()=>resolve(true)));
  });
})();
