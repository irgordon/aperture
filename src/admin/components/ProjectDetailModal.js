import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const ProjectDetailModal = ({ project, onClose }) => {
    const [activeTab, setActiveTab] = useState('overview');
    const [tasks, setTasks] = useState([]);
    useEffect(() => { if(project.id) apiFetch({ path: `/aperture/v1/tasks?lead_id=${project.id}` }).then(setTasks); }, [project]);
    const addTask = async (e) => {
        if(e.key === 'Enter' && e.target.value.trim() !== '') {
            const t = await apiFetch({ path: '/aperture/v1/tasks', method: 'POST', data: { lead_id: project.id, description: e.target.value, due_date: new Date().toISOString().split('T')[0] } });
            setTasks([...tasks, { id: t.id, description: e.target.value, is_completed: 0 }]); e.target.value = '';
        }
    };
    return (
        <div className="ap-modal-overlay"><div className="ap-modal full-height">
            <header className="modal-header"><h2>{project.title || 'New Project'}</h2><button onClick={onClose}>×</button></header>
            <div className="project-layout">
                <aside className="project-sidebar">
                    <nav><button onClick={()=>setActiveTab('overview')}>Overview</button><button onClick={()=>setActiveTab('documents')}>Documents</button></nav>
                    <div className="checklist-widget"><h4>Tasks</h4><input placeholder="+ Task (Enter)" onKeyDown={addTask} /><ul>{tasks.map(t=><li key={t.id}>{t.description}</li>)}</ul></div>
                </aside>
                <main className="project-content">{activeTab==='overview' && <div><p>Value: ${project.project_value}</p><p>Notes: {project.notes}</p></div>}</main>
            </div>
        </div></div>
    );
};
export default ProjectDetailModal;
