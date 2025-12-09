import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import DashboardMetrics from './DashboardMetrics';
import ProjectDetailModal from './ProjectDetailModal';

const LeadList = () => {
    const [leads, setLeads] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);
    const stages = [{id:'inquiry', label:'Inquiry'}, {id:'proposal', label:'Proposal'}, {id:'contract', label:'Contract'}, {id:'booked', label:'Booked'}, {id:'completed', label:'Completed'}];

    useEffect(() => { apiFetch({ path: '/aperture/v1/leads' }).then(setLeads); }, []);

    const moveStage = async (id, newStage) => {
        setLeads(leads.map(l => l.id === id ? { ...l, stage: newStage } : l));
        await apiFetch({ path: `/aperture/v1/leads/${id}`, method: 'POST', data: { stage: newStage } });
    };

    return (
        <div className="ap-pipeline">
            <DashboardMetrics />
            <header className="pipeline-header">
                <h2>Project Pipeline</h2>
                <button className="btn-primary" onClick={() => setSelectedProject({})}>+ New Project</button>
            </header>
            <div className="pipeline-board">
                {stages.map(stage => (
                    <div key={stage.id} className="pipeline-column">
                        <div className="column-header"><span className="stage-name">{stage.label}</span><span className="count">{leads.filter(l => l.stage === stage.id).length}</span></div>
                        <div className="column-body">
                            {leads.filter(l => l.stage === stage.id).map(lead => (
                                <div key={lead.id} className="project-card" onClick={() => setSelectedProject(lead)}>
                                    <div className="card-top"><strong>{lead.title}</strong><span>${lead.project_value}</span></div>
                                    <div className="client-name">{lead.first_name} {lead.last_name}</div>
                                    <div className="card-footer"><span className="date">{lead.event_date}</span>{stage.id !== 'completed' && <button className="btn-icon" onClick={(e) => { e.stopPropagation(); moveStage(lead.id, stages[stages.findIndex(s=>s.id===stage.id)+1].id); }}>→</button>}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
            {selectedProject && <ProjectDetailModal project={selectedProject} onClose={() => setSelectedProject(null)} onUpdate={() => apiFetch({ path: '/aperture/v1/leads' }).then(setLeads)} />}
        </div>
    );
};
export default LeadList;
