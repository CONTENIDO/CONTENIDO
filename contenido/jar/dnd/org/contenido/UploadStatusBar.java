package org.contenido;

import java.awt.Button;

import java.awt.Color;
import java.awt.Graphics;
import java.awt.Panel;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;

import javax.swing.JLabel;
import javax.swing.JProgressBar;


/**
 * A single upload in the UploadStatusShelf. It keeps track of its status all by itself, draws itself in the right position and listens to the events of the cancel button.
 * 
 * @author Mischa Holz
 *
 *@version 1.1, 01/13/2012
 *
 * @since 1.0
 */
public class UploadStatusBar extends Panel implements ActionListener {
	private static final long serialVersionUID = 1L;
	
	private File fileToUpload;
	private UploadThread worker;
	
	private JProgressBar progressBar;
	private Button cancelButton;
	private JLabel title;
	private JLabel status;
	
	private boolean running;
	private String uid;
	private String uploadPath;
	private String host;
	private UploadStatusShelf uss;
	
	/**
	 * Updates the size and the position of the components in the bar.
	 */
	public void updateSize() {
		this.setBounds(DropboxMain.shelfX, uss.getPosition(this) * DropboxMain.barHeight, DropboxMain.appletWidth, DropboxMain.barHeight);
		progressBar.setBounds(DropboxMain.progressBarX, DropboxMain.progressBarY, DropboxMain.progressBarWidth, DropboxMain.progressBarHeight);
		cancelButton.setBounds(DropboxMain.cancelButtonX, DropboxMain.cancelButtonY, DropboxMain.cancelButtonWidth, DropboxMain.cancelButtonHeight);
		title.setBounds(DropboxMain.barTitleLabelX, DropboxMain.barTitleLabelY, DropboxMain.barTitleLabelWidth, DropboxMain.barTitleLabelHeight);
		status.setBounds(DropboxMain.barStatusLabelX, DropboxMain.barStatusLabelY, DropboxMain.barStatusLabelWidth, DropboxMain.barStatusLabelHeight);
	}
	
	/**
	 * The thread initializing and performing the upload of the file. It updates the progress bar and the labels to represent the current status of the upload.
	 * @author Mischa Holz
	 *
	 * @version 1.1, 01/13/2012
	 * 
	 * @since 1.0
	 */
	private class UploadThread extends Thread {
		/**
		 * The main method of the thread. It connects to the server via HTTP, reads the file from the FS and uploads it to the server.
		 */
		public void run() {
			try {
				//strings for HTTP
		        String lineEnd = "\r\n";
		        String hyphens = "--";
		        String boundary =  "---------------------------fggdsasdfghjkl";
		        
		        //create the URL and the connection
				URL url = new URL(host + "main.php");
				HttpURLConnection conn = (HttpURLConnection) url.openConnection();
				
				long fileSize = fileToUpload.length();
				
				conn.setDoOutput(true);
	            conn.setRequestProperty("Content-Type", "multipart/form-data; boundary=" + boundary);
	            
				String endend = hyphens + boundary + hyphens + lineEnd;
				
				//build the HTTP form data
				String params = hyphens + boundary + lineEnd + "Content-Disposition: form-data; name=\"frame\"" + lineEnd + lineEnd + "4" + lineEnd;
				params += hyphens + boundary + lineEnd + "Content-Disposition: form-data; name=\"area\"" + lineEnd + lineEnd + "upl" + lineEnd;
				params += hyphens + boundary + lineEnd + "Content-Disposition: form-data; name=\"contenido\"" + lineEnd + lineEnd + uid + lineEnd;
				params += hyphens + boundary + lineEnd + "Content-Disposition: form-data; name=\"action\"" + lineEnd + lineEnd + "upl_upload" + lineEnd;
				if(uploadPath != null) {
					params += hyphens + boundary + lineEnd + "Content-Disposition: form-data; name=\"path\"" + lineEnd + lineEnd + uploadPath + lineEnd;
				}
				String fileType = HttpURLConnection.guessContentTypeFromName(fileToUpload.getName());
				if(fileType == null) {
					fileType = "application/octet-stream";
				}
				
				String start = hyphens + boundary + lineEnd + "Content-Disposition: form-data; name=\"file[]\"; filename=\"" + fileToUpload.getName() +"\"" + lineEnd + "Content-Type: " + fileType + lineEnd + lineEnd;
				
				conn.setFixedLengthStreamingMode((int) (fileSize + start.length() + params.length() + endend.length() + lineEnd.length()));
				
				//start the connection and write the beginning of the file content
				DataOutputStream dos = new DataOutputStream(conn.getOutputStream()); 
				dos.writeBytes(start);
	            
				if(!fileToUpload.isDirectory()) {
					int bytesRead = 0;
					long sumBytes = 0;
					int bps = 0;
					long lastBPS = System.currentTimeMillis() - 100;
					
					FileInputStream fis = new FileInputStream(fileToUpload);
					
					int bufferSize = Math.min(fis.available(), 1024 * 1024);
					
			        byte[] buffer = new byte[bufferSize];
					
			        //read the file from disk and send it to the server
			        do {
						
						bytesRead = fis.read(buffer, 0, bufferSize);
						if(bytesRead == -1) {
							break;
						}
						dos.write(buffer, 0, bytesRead);

						sumBytes += bytesRead;
						bps += bytesRead;
						
						progressBar.setValue((int)(((double)((double)sumBytes / (double)fileSize)) * 100));
						
						/*try {
							Thread.sleep(500);
						} catch (InterruptedException e) {
							e.printStackTrace();
						}*/
				
						//Calculate upload speed
						if(System.currentTimeMillis() - lastBPS > 100 && running) {
							status.setText(DropboxMain.bytesForHuman(sumBytes) + "/" + DropboxMain.bytesForHuman(fileSize) + " | " + DropboxMain.bytesForHuman(bps * 10) + "/s");
							bps = 0;
							lastBPS = System.currentTimeMillis();
							updateSize();
							repaint();
						}
					} while(bytesRead > 0 && running);
				} else {
					status.setText("Nur Dateien hochladen");
					return;
				}
				
				if(running == false) {
					status.setText("Cancelled");
					remove(cancelButton);
					return;
				}
				
				//write the additional parameters and end the form data
				dos.writeBytes(lineEnd + params);
	            dos.writeBytes(endend);
	            
	            //flush and close the output buffer
				dos.flush();
				dos.close();

				//read the response
	            BufferedReader br = new BufferedReader(new InputStreamReader(conn.getInputStream()));
	            String line = "";
	            String response = "";
	            while((line = br.readLine()) != null) {
	            	response += line;
	            }
				
	            //update UI
				if(running == true) {
					status.setText("Finished");
					remove(cancelButton);
					repaint();
					
					try {
						Thread.sleep(3000);
					} catch (InterruptedException e) {
						e.printStackTrace();
					}
					
					removeMe(response);
				}
			} catch (MalformedURLException e) {
				updateSize();
				
				status.setText(e.getLocalizedMessage());
				return;
			} catch (IOException e) {
				updateSize();
			
				status.setText("Could not read the file");
				return;
			} catch (Exception e) {
				updateSize();
				
				status.setText(e.getLocalizedMessage());
				return;
			}
		}
	}
	
	/**
	 * Removes this component from the UploadStatusShelf, which recieves the HTML response <code>aresponse</code>.
	 * @param aresponse	the response of the Webserver after finishing the upload
	 */
	public void removeMe(String aresponse) {
		uss.remove(this, aresponse);
	}
	
	/**
	 * Returns the current instance of this class to pass it to the thread.
	 * @return <code>this</code>
	 */
	public UploadStatusBar getMe() {
		return this;
	}
	
	/**
	 * Gets called when the cancel button is pressed and aborts the corresponding upload by setting running to false. It also changes the status label to reflect the status to the user.
	 */
	public void actionPerformed(ActionEvent ae) {
		running = false;
		
		status.setText("Cancelling...");
		
		this.remove(cancelButton);
	}
	
	/**
	 * Gets called when the component has to redraw itself. It calls <code>super.draw(g)</code>, updates the size of the components within it and draws the borders of the upload bar.
	 */
	public void paint(Graphics g) {
		super.paint(g);

		updateSize();
		
		g.setColor(Color.black);
		g.drawLine(getWidth() - 1, 0, getWidth() - 1, getHeight() - 1);
		g.drawLine(getWidth() - 1, getHeight() - 1, 0, getHeight() - 1);
	}
	
	/**
	 * Creates an instance of the class and starts the upload of the file <code>afile</file> asynchronous.
	 * @param auss the instance of the <code>UploadStatusShelf</code> to which this bar belongs.
	 * @param ahost the path to the main.php which ends with /
	 * @param afile the file to upload to the server
	 * @param auid the CONTENIDO id of the current user
	 * @param auploadPath the relative path to upload to the CONTENIDO backend
	 */
	public UploadStatusBar(UploadStatusShelf auss, String ahost, File afile, String auid, String auploadPath) {
		host = ahost;
		uss = auss;
		fileToUpload = afile;
		worker = new UploadThread();
		running = true;
		uid = auid;
		uploadPath = auploadPath;
		
		this.setBackground(Color.white);
		
		progressBar = new JProgressBar();
		progressBar.setBounds(DropboxMain.progressBarX, DropboxMain.progressBarY, DropboxMain.progressBarWidth, DropboxMain.progressBarHeight);
		progressBar.setBackground(Color.white);
		this.add(progressBar);
		
		cancelButton = new Button("Abbrechen");
		cancelButton.setBounds(DropboxMain.cancelButtonX, DropboxMain.cancelButtonY, DropboxMain.cancelButtonWidth, DropboxMain.cancelButtonHeight);
		cancelButton.addActionListener(this);
		cancelButton.setBackground(Color.white);
		this.add(cancelButton);
		
		title = new JLabel(afile.getName() + "...");
		title.setBounds(DropboxMain.barTitleLabelX, DropboxMain.barTitleLabelY, DropboxMain.barTitleLabelWidth, DropboxMain.barTitleLabelHeight);
		title.setBackground(Color.white);
		this.add(title);
		
		status = new JLabel("Starting...");
		status.setBounds(DropboxMain.barStatusLabelX, DropboxMain.barStatusLabelY, DropboxMain.barStatusLabelWidth, DropboxMain.barStatusLabelHeight);
		status.setBackground(Color.white);
		this.add(status);
		
		worker.start();
	}
}
