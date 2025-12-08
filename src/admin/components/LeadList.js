import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import CreateInvoiceModal from './CreateInvoiceModal';

const LeadList = () => {
    const [leads, setLeads] = useState([]);
    const [modalOpen, setModalOpen] = useState(false);

    useEffect(() => {
        apiFetch({path: '/aperture/v1/leads'}).then(setLeads);
    }, []);

    return (
        <div className="ap-leads">
            <div className="header">
                <h2>Pipeline</h2>
                <button className="btn-primary" onClick={() => setModalOpen(true)}>+ Create Invoice</button>
            </div>
            
            <div className="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Value</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        {leads.map(l => (
                            <tr key={l.id}>
                                <td><strong>{l.first_name} {l.last_name}</strong></td>
                                <td>
                                    <span className={`badge ${l.status ? l.status.toLowerCase() : 'new'}`}>{l.status}</span>
                                </td>
                                <td>${l.project_value}</td>
                                <td style={{color:'#888', fontSize:'13px'}}>{l.source || 'Web'}</td>
                            </tr>
                        ))}
                        {leads.length === 0 && (
                            <tr><td colSpan="4" style={{textAlign:'center', padding:'30px', color:'#888'}}>No leads found. Create your first invoice!</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            <CreateInvoiceModal isOpen={modalOpen} onClose={()=>setModalOpen(false)} leads={leads} />
        </div>
    );
};
export default LeadList;
