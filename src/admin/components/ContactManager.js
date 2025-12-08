import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const ContactManager = () => {
    const [contacts, setContacts] = useState([]);
    const [isEditing, setIsEditing] = useState(false);
    const [form, setForm] = useState({ id: '', first_name: '', last_name: '', email: '', phone: '', address: '' });

    useEffect(() => { load(); }, []);
    const load = () => apiFetch({ path: '/aperture/v1/contacts' }).then(setContacts);

    const save = async (e) => {
        e.preventDefault();
        await apiFetch({ path: '/aperture/v1/contacts', method: 'POST', data: form });
        setIsEditing(false);
        setForm({ id: '', first_name: '', last_name: '', email: '', phone: '', address: '' });
        load();
    };

    return (
        <div className="ap-contacts">
            <div className="header" style={{display:'flex', justifyContent:'space-between', marginBottom:'20px'}}>
                <h2>Customer Database</h2>
                <button className="btn-primary" onClick={() => setIsEditing(true)}>+ Add Customer</button>
            </div>
            {isEditing && (
                <div className="ap-card" style={{marginBottom:'20px'}}>
                    <h3>{form.id ? 'Edit' : 'Add'} Customer</h3>
                    <form onSubmit={save} style={{display:'grid', gap:'10px', gridTemplateColumns:'1fr 1fr'}}>
                        <input placeholder="First Name" value={form.first_name} onChange={e=>setForm({...form, first_name:e.target.value})} required/>
                        <input placeholder="Last Name" value={form.last_name} onChange={e=>setForm({...form, last_name:e.target.value})} required/>
                        <input placeholder="Email" value={form.email} onChange={e=>setForm({...form, email:e.target.value})} required/>
                        <input placeholder="Phone" value={form.phone} onChange={e=>setForm({...form, phone:e.target.value})}/>
                        <textarea placeholder="Address" value={form.address} onChange={e=>setForm({...form, address:e.target.value})} style={{gridColumn:'span 2'}}/>
                        <div style={{gridColumn:'span 2'}}>
                            <button className="btn-primary">Save</button>
                            <button type="button" className="btn-secondary" onClick={()=>setIsEditing(false)} style={{marginLeft:'10px'}}>Cancel</button>
                        </div>
                    </form>
                </div>
            )}
            <div className="table-container">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Action</th></tr></thead>
                    <tbody>
                        {contacts.map(c => (
                            <tr key={c.id}>
                                <td>{c.first_name} {c.last_name}</td>
                                <td>{c.email}</td>
                                <td>{c.phone}</td>
                                <td><button className="btn-secondary small" onClick={()=>{setForm(c); setIsEditing(true)}}>Edit</button></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};
export default ContactManager;
