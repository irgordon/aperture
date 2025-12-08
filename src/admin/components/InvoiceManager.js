import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const InvoiceManager = () => {
    const [invoices, setInvoices] = useState([]);
    useEffect(() => { apiFetch({path: '/aperture/v1/invoices'}).then(setInvoices).catch(()=>setInvoices([])); }, []);

    return (
        <div className="ap-container">
            <header className="ap-header"><h2>Invoice History</h2></header>
            <div className="table-container">
                <table>
                    <thead><tr><th>Invoice #</th><th>Client</th><th>Amount</th><th>Due Date</th><th>Status</th></tr></thead>
                    <tbody>
                        {invoices.map(inv => (
                            <tr key={inv.id}>
                                <td>{inv.invoice_number}</td>
                                <td>{inv.first_name} {inv.last_name}</td>
                                <td>${inv.amount}</td>
                                <td>{inv.due_date}</td>
                                <td><span className={`badge ${inv.status}`}>{inv.status}</span></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};
export default InvoiceManager;
