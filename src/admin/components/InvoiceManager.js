import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const InvoiceManager = () => {
    const [invoices, setInvoices] = useState([]);

    useEffect(() => {
        apiFetch({path: '/aperture/v1/invoices'})
            .then(setInvoices)
            .catch(() => setInvoices([]));
    }, []);

    return (
        <div className="ap-container">
            <div className="ap-header">
                <h2>Invoices</h2>
                <button className="btn-primary">+ Create Invoice</button>
            </div>
            
            <div className="ap-card" style={{padding:0, overflow:'hidden'}}>
                <table className="ap-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {invoices.map(inv => (
                            <tr key={inv.id}>
                                <td><strong>{inv.invoice_number}</strong></td>
                                <td>{inv.first_name} {inv.last_name}</td>
                                <td>${inv.total_amount}</td>
                                <td>{inv.due_date}</td>
                                <td>
                                    <span className={`badge ${inv.status}`}>
                                        {inv.status}
                                    </span>
                                </td>
                                <td>
                                    <button className="btn-secondary" style={{padding:'4px 10px', fontSize:'12px'}}>View</button>
                                </td>
                            </tr>
                        ))}
                        {invoices.length === 0 && (
                            <tr><td colSpan="6" style={{textAlign:'center', padding:'20px'}}>No invoices found.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
};
export default InvoiceManager;
