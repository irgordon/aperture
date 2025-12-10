import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const ProjectDetailModal = ({ project, onClose, onUpdate }) => {
    const [activeTab, setActiveTab] = useState('overview');
    const [tasks, setTasks] = useState([]);

    // Form State
    const [formData, setFormData] = useState({
        title: project.title || '',
        admin_id: project.assigned_to || '',
        contact_id: project.contact_id || '',
        notes: project.notes || '',
        project_value: project.project_value || ''
    });

    const [admins, setAdmins] = useState([]);
    const [customers, setCustomers] = useState([]);

    useEffect(() => {
        // Fetch tasks if project exists
        if(project.id) apiFetch({ path: `/aperture/v1/tasks?lead_id=${project.id}` }).then(setTasks);

        // Fetch users (admins) and customers
        apiFetch({ path: '/aperture/v1/auth/users' }).then(setAdmins).catch(() => {}); // Assuming endpoint exists or we create it
        apiFetch({ path: '/aperture/v1/contacts' }).then(setCustomers);
    }, [project]);

    const saveProject = async () => {
        const path = project.id ? `/aperture/v1/leads/${project.id}` : '/aperture/v1/leads';
        await apiFetch({ path, method: 'POST', data: formData });
        onUpdate();
        if(!project.id) onClose();
    };

    const addTask = async (e) => {
        if(e.key === 'Enter' && e.target.value.trim() !== '') {
            const t = await apiFetch({
                path: '/aperture/v1/tasks',
                method: 'POST',
                data: { lead_id: project.id, description: e.target.value, priority: 'medium', status: 'pending', due_date: new Date().toISOString().split('T')[0] }
            });
            setTasks([...tasks, { ...t, is_completed: 0 }]);
            e.target.value = '';
        }
    };

    const updateTask = async (task, updates) => {
        const updatedTask = { ...task, ...updates };
        setTasks(tasks.map(t => t.id === task.id ? updatedTask : t));
        await apiFetch({ path: `/aperture/v1/tasks/${task.id}`, method: 'PUT', data: updates });
    };

    return (
        <div className="ap-modal-overlay"><div className="ap-modal full-height">
            <header className="modal-header">
                <h2>{project.id ? 'Edit Project' : 'New Project'}</h2>
                <button onClick={onClose}>×</button>
            </header>

            <div className="project-layout">
                <aside className="project-sidebar">
                    <div className="form-group">
                        <label>Project Name</label>
                        <input value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} placeholder="e.g. Smith Wedding" />
                    </div>

                    <div className="form-group">
                        <label>Customer</label>
                        <select value={formData.contact_id} onChange={e => setFormData({...formData, contact_id: e.target.value})}>
                            <option value="">Select Customer</option>
                            {customers.map(c => <option key={c.id} value={c.id}>{c.first_name} {c.last_name}</option>)}
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Assigned Admin</label>
                        <select value={formData.admin_id} onChange={e => setFormData({...formData, admin_id: e.target.value})}>
                            <option value="">Select Admin</option>
                            {admins.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Value ($)</label>
                        <input type="number" value={formData.project_value} onChange={e => setFormData({...formData, project_value: e.target.value})} />
                    </div>

                    <button className="btn-primary" style={{width:'100%', marginTop:'10px'}} onClick={saveProject}>Save Project</button>

                    <hr/>

                    {project.id && (
                        <div className="checklist-widget">
                            <h4>Tasks</h4>
                            <input placeholder="+ Task (Enter)" onKeyDown={addTask} />
                            <ul className="task-list">
                                {tasks.map(t => (
                                    <li key={t.id} className="task-item">
                                        <div className="task-header">
                                            <span>{t.description}</span>
                                            <select
                                                value={t.status || 'pending'}
                                                onChange={(e) => updateTask(t, { status: e.target.value })}
                                                className={`status-badge ${t.status}`}
                                                style={{fontSize: '10px', padding: '2px'}}
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="on_hold">On Hold</option>
                                            </select>
                                        </div>
                                        <div className="task-meta">
                                            <select
                                                value={t.priority || 'medium'}
                                                onChange={(e) => updateTask(t, { priority: e.target.value })}
                                                style={{fontSize: '10px', border:'none', background:'transparent'}}
                                            >
                                                <option value="low">Low</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                            </select>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </aside>

                <main className="project-content">
                    {activeTab==='overview' && (
                        <div>
                            <h3>Notes</h3>
                            <textarea
                                value={formData.notes}
                                onChange={e => setFormData({...formData, notes: e.target.value})}
                                rows="10"
                                style={{width:'100%', border:'1px solid #ddd', padding:'10px'}}
                            />
                        </div>
                    )}
                </main>
            </div>
        </div></div>
    );
};
export default ProjectDetailModal;
