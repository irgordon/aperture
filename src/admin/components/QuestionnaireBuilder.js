import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const QuestionnaireBuilder = () => {
    const [title, setTitle] = useState('');
    const [questions, setQuestions] = useState([]);

    const addQ = (type) => setQuestions([...questions, { id: Date.now(), type, label: '', required: false }]);
    const updateQ = (id, field, val) => setQuestions(questions.map(q => q.id === id ? { ...q, [field]: val } : q));
    const save = () => apiFetch({ path: '/aperture/v1/questionnaires', method: 'POST', data: { title, questions } }).then(() => alert('Saved!'));

    return (
        <div className="builder">
            <h2>Form Builder</h2>
            <input value={title} onChange={e => setTitle(e.target.value)} placeholder="Form Title" />
            <div className="stack">
                {questions.map((q, i) => (
                    <div key={q.id} className="q-card">
                        <span>Q{i+1} ({q.type})</span>
                        <input value={q.label} onChange={e => updateQ(q.id, 'label', e.target.value)} placeholder="Question Label" />
                    </div>
                ))}
            </div>
            <div className="controls">
                <button className="btn-secondary" onClick={() => addQ('text')}>+ Text</button>
                <button className="btn-secondary" onClick={() => addQ('textarea')}>+ Long Text</button>
                <button className="btn-secondary" onClick={() => addQ('date')}>+ Date</button>
                <button className="save btn-primary" onClick={save}>Save Form</button>
            </div>
        </div>
    );
};
export default QuestionnaireBuilder;
