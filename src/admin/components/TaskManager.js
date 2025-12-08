import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const TaskManager = () => {
    const [tasks, setTasks] = useState([]);
    const [newTask, setNewTask] = useState({ description: '', due_date: '', lead_id: 0 });
    const [leads, setLeads] = useState([]);

    useEffect(() => { loadTasks(); apiFetch({path: '/aperture/v1/leads'}).then(setLeads); }, []);
    const loadTasks = () => apiFetch({path: '/aperture/v1/tasks'}).then(setTasks);

    const addTask = async (e) => {
        e.preventDefault();
        await apiFetch({ path: '/aperture/v1/tasks', method: 'POST', data: newTask });
        setNewTask({ description: '', due_date: '', lead_id: 0 });
        loadTasks();
    };
    const toggleTask = async (task) => { await apiFetch({ path: `/aperture/v1/tasks/${task.id}`, method: 'PUT', data: { is_completed: !task.is_completed } }); loadTasks(); };
    const deleteTask = async (id) => { if(!confirm('Delete task?')) return; await apiFetch({ path: `/aperture/v1/tasks/${id}`, method: 'DELETE' }); loadTasks(); };

    return (
        <div className="ap-container">
            <header className="ap-header"><h2>Project Tasks</h2></header>
            <div className="ap-grid-2">
                <div className="ap-card">
                    <h3>Add New Task</h3>
                    <form onSubmit={addTask}>
                        <div className="form-group"><label>Description</label><input value={newTask.description} onChange={e=>setNewTask({...newTask, description:e.target.value})} required/></div>
                        <div className="form-group"><label>Due Date</label><input type="date" value={newTask.due_date} onChange={e=>setNewTask({...newTask, due_date:e.target.value})} required/></div>
                        <div className="form-group"><label>Project</label><select value={newTask.lead_id} onChange={e=>setNewTask({...newTask, lead_id:e.target.value})}><option value="0">General</option>{leads.map(l => <option key={l.id} value={l.id}>{l.first_name} {l.last_name}</option>)}</select></div>
                        <button className="btn-primary">Add Task</button>
                    </form>
                </div>
                <div className="ap-card">
                    <h3>To-Do List</h3>
                    <ul className="ap-list">
                        {tasks.map(t => (
                            <li key={t.id} className={t.is_completed ? 'completed' : ''}>
                                <label><input type="checkbox" checked={t.is_completed == 1} onChange={() => toggleTask(t)} /><span>{t.description}</span><small>{t.due_date}</small></label>
                                <button className="btn-icon" onClick={() => deleteTask(t.id)}>🗑</button>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );
};
export default TaskManager;
