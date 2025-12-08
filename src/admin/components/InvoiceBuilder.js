import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const InvoiceBuilder = ({ leadId, onClose }) => {
    const [invoice, setInvoice] = useState({ lead_id: leadId, items: [{ description: 'Photography Session', price: 0, qty: 1 }], settings: { tax_rate: 0, service_fee: 0, deposit: 0 }, due_date: '' });

    const updateItem = (index, field, value) => {
        const newItems = [...invoice.items];
        newItems[index][field] = value;
        setInvoice({ ...invoice, items: newItems });
    };
    const addItem = () => setInvoice({ ...invoice, items: [...invoice.items, { description: '', price: 0, qty: 1 }] });
    const calculateTotal = () => {
        const subtotal = invoice.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const tax = subtotal * (invoice.settings.tax_rate / 100);
        const fee = Number(invoice.settings.service_fee);
        return (subtotal + tax + fee).toFixed(2);
    };
    const save = async () => {
        const total = calculateTotal();
        await apiFetch({ path: '/aperture/v1/invoices', method: 'POST', data: { ...invoice, total_amount: total } });
        alert('Invoice Created'); onClose();
    };

    return (
        <div className="ap-modal-overlay">
            <div className="ap-modal large">
                <h3>Create Invoice</h3>
                <div className="line-items">
                    <div className="row header" style={{display:'grid', gridTemplateColumns:'2fr 1fr 1fr 1fr', gap:'10px', fontWeight:'bold', marginBottom:'10px'}}><span>Description</span><span>Price</span><span>Qty</span><span>Total</span></div>
                    {invoice.items.map((item, i) => (
                        <div key={i} className="row" style={{display:'grid', gridTemplateColumns:'2fr 1fr 1fr 1fr', gap:'10px', marginBottom:'10px'}}>
                            <input value={item.description} onChange={e => updateItem(i, 'description', e.target.value)} placeholder="Service Name" />
                            <input type="number" value={item.price} onChange={e => updateItem(i, 'price', parseFloat(e.target.value))} />
                            <input type="number" value={item.qty} onChange={e => updateItem(i, 'qty', parseFloat(e.target.value))} />
                            <span>${(item.price * item.qty).toFixed(2)}</span>
                        </div>
                    ))}
                    <button className="btn-secondary small" onClick={addItem}>+ Add Item</button>
                </div>
                <div className="invoice-meta" style={{marginTop:'20px'}}>
                    <label>Tax Rate (%) <input type="number" onChange={e => setInvoice({...invoice, settings: {...invoice.settings, tax_rate: e.target.value}})} /></label>
                    <label>Service Fee ($) <input type="number" onChange={e => setInvoice({...invoice, settings: {...invoice.settings, service_fee: e.target.value}})} /></label>
                    <label>Due Date <input type="date" onChange={e => setInvoice({...invoice, due_date: e.target.value})} /></label>
                </div>
                <div className="total-bar" style={{marginTop:'20px', borderTop:'1px solid #eee', paddingTop:'10px', display:'flex', justifyContent:'space-between', alignItems:'center'}}>
                    <h4>Total: ${calculateTotal()}</h4>
                    <div><button className="btn-primary" onClick={save}>Create & Send</button><button className="btn-secondary" onClick={onClose} style={{marginLeft:'10px'}}>Cancel</button></div>
                </div>
            </div>
        </div>
    );
};
export default InvoiceBuilder;
