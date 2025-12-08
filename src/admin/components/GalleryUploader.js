import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const GalleryUploader = ({ albumId }) => {
    const [progress, setProgress] = useState(0);

    const handleUpload = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('file_name', file.name);
        formData.append('album_id', albumId);
        await apiFetch({ path: '/aperture/v1/gallery/upload', method: 'POST', body: formData });
        setProgress(100);
        alert('Uploaded!');
    };

    return (
        <div className="ap-card" style={{border:'2px dashed #ccc', textAlign:'center'}}>
            <input type="file" onChange={handleUpload} />
            {progress > 0 && <p>Progress: {progress}%</p>}
        </div>
    );
};
export default GalleryUploader;
